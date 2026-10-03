<?php
// action.php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use Parse\ParseUser;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseACL;

// Указываем, что отдаем JSON
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
$isJsonRequest = stripos($contentType, 'application/json') !== false;
$input = $isJsonRequest ? json_decode($raw, true) : [];

if ($isJsonRequest && $raw && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "success" => false,
        "error" => json_last_error_msg()
    ]);
    exit;
}

$input = is_array($input) ? $input : [];
$act = $input['act'] ?? $_POST['act'] ?? $_GET['act'] ?? '';

$response = ['success' => false];

function makeUsersHash(array $userIds)
{
    sort($userIds);
    return implode('_', $userIds);
}

function makeChatMembersAcl(array $chatUsers)
{
    $acl = new ParseACL();
    $acl->setPublicReadAccess(false);
    foreach ($chatUsers as $u) {
        $acl->setReadAccess($u->getObjectId(), true);
        $acl->setWriteAccess($u->getObjectId(), true);
    }
    return $acl;
}

try {
    if (!$currentUser && $act !== 'login' && $act !== 'register') {
        throw new Exception("Unauthorized");
    }

    if ($act === 'register') {
        $user = new ParseUser();
        $user->setUsername($_POST['username']);
        $user->setPassword($_POST['password']);

        parseRetry(function () use ($user) {
            $user->signUp();
        });

        $acl = new ParseACL();
        $acl->setPublicReadAccess(true);
        $acl->setWriteAccess($user->getObjectId(), true);
        $user->setACL($acl);
        parseRetry(function () use ($user) {
            $user->save();
        });

        header("Location: /profile.php");
        exit;
    }
    // --- ЛОГИН (Обычная форма, нужен редирект) ---
    if ($act === 'login') {
        $user = parseRetry(function () {
            return ParseUser::logIn($_POST['username'], $_POST['password']);
        });
        header("Location: /profile.php");
        exit;
    }

    // --- ВЫХОД ---
    if ($act === 'logout') {
        ParseUser::logOut();
        header("Location: /index.php");
        exit;
    }

    if ($act === 'approve' || $act === 'reject') {
        if (!$currentUser || $currentUser->get('role') !== 'admin') {
            throw new Exception("Access denied");
        }

        $articleId = $input['id'] ?? $_POST['id'] ?? $_GET['id'] ?? '';
        if (!$articleId) {
            throw new Exception("Missing article id");
        }

        $q = new ParseQuery("Article");
        $post = parseRetry(function () use ($q, $articleId) {
            return $q->get($articleId);
        });

        if ($act === 'approve') {
            $post->set("status", "approved");
            $post->set("moderationReason", null);
        } else {
            $post->set("status", "rejected");
            $post->set("moderationReason", $_POST['reason'] ?? $input['reason'] ?? '');
        }

        parseRetry(function () use ($post) {
            $post->save();
        });

        header("Location: /admin.php");
        exit;
    }

    // --- ГОЛОСОВАНИЕ (AJAX) ---
    if ($act === 'vote') {
        $articleId = $input['id'];
        $type = $input['type']; // 'up' или 'down'

        $q = new ParseQuery("Article");
        $post = parseRetry(function () use ($q, $articleId) {
            return $q->get($articleId);
        });

        $likes = $post->get("likes") ?: [];
        $dislikes = $post->get("dislikes") ?: [];
        $userId = $currentUser->getObjectId();

        $likes = array_values(array_diff($likes, [$userId]));
        $dislikes = array_values(array_diff($dislikes, [$userId]));

        if ($type === 'up') {
            $likes[] = $userId;
        } else {
            $dislikes[] = $userId;
        }

        $newScore = count($likes) - count($dislikes);

        // ВАЖНО: Используем setArray для массивов, чтобы избежать ошибки
        $post->setArray("likes", $likes);
        $post->setArray("dislikes", $dislikes);
        $post->set("score", $newScore);
        parseRetry(function () use ($post) {
            $post->save();
        });

        echo json_encode(['success' => true, 'newScore' => $newScore]);
        exit;
    }

    if ($act === 'fav') {
        $articleId = $input['id'];
        $q = new ParseQuery("Article");
        $post = parseRetry(function () use ($q, $articleId) {
            return $q->get($articleId);
        });

        $relation = $currentUser->getRelation("favorites");

        // Получаем relation и ищем пост
        $checkQuery = $relation->getQuery();
        $checkQuery->equalTo("objectId", $post->getObjectId());

        $existingPosts = parseRetry(function () use ($checkQuery) {
            return $checkQuery->find();
        }); // вернёт массив

        $is_added = false;

        if (count($existingPosts) > 0) {
            $relation->remove($post);
            if (isset($_SESSION['favorites'][$post->getObjectId()])) {
                unset($_SESSION['favorites'][$post->getObjectId()]);
            }
        } else {
            $relation->add($post);
            $is_added = true;
            $_SESSION['favorites'][$post->getObjectId()] = true;
        }

        parseRetry(function () use ($currentUser) {
            $currentUser->save();
        });
        echo json_encode(['success' => true, 'favorite' => $is_added]);
        exit;
    }

    if ($act === 'presence_ping') {
        $currentUser->set('lastSeenAt', new DateTime());
        parseRetry(function () use ($currentUser) {
            $currentUser->save();
        });

        echo json_encode(['success' => true]);
        exit;
    }

    if ($act === 'chat_heartbeat') {
        $chat = parseRetry(function () use ($input) {
            return (new ParseQuery('Chat'))->get($input['chat_id']);
        });

        $q = new ParseQuery('ChatState');
        $q->equalTo('chat', $chat);
        $q->equalTo('user', $currentUser);
        $state = parseRetry(function () use ($q) {
            return $q->first();
        });

        if (!$state) {
            $state = new ParseObject('ChatState');
            $state->set('chat', $chat);
            $state->set('user', $currentUser);
            $state->set('unreadCount', 0);
            $state->setACL(makeChatMembersAcl($chat->get('users')));
        }

        $state->set('lastActiveAt', new DateTime());
        parseRetry(function () use ($state) {
            $state->save();
        });

        echo json_encode(['success' => true]);
        exit;
    }


    // --- КОММЕНТАРИЙ (AJAX) ---
    if ($act === 'comment') {
        $articleId = $input['article_id'];
        $text = $input['text'] ?? '';

        if (empty(trim($text))) { 
            throw new Exception("Empty comment");
        }

        $q = new ParseQuery("Article");
        $post = parseRetry(function () use ($q, $articleId) {
            return $q->get($articleId);
        });

        $comment = new ParseObject("Comment");
        $comment->set("text", $text);
        $comment->set("author", $currentUser);
        $comment->set("article", $post);
        $comment->save();

        // Возвращаем данные, чтобы JS мог отрисовать коммент сразу
        echo json_encode([
            'success' => true,
            'author' => $currentUser->get('username'),
            'text' => $text,
            'date' => date('d.m.Y H:i')
        ]);
        exit;
    }



    // --- CREATE POST ---
    if ($act === 'create_post' && $currentUser) {
        $post = new ParseObject("Article");
        $post->set("title", $_POST['title']);
        $post->set("content", $_POST['content']);
        $post->set("author", $currentUser);
        $post->set("status", "pending"); // Модерация
        $post->set("views", 0);
        $post->set("score", 0);

        $acl = new ParseACL();
        $acl->setPublicReadAccess(true);
        $acl->setPublicWriteAccess(true);
        $post->setACL($acl);

        $post->save();
        header("Location: /profile.php");
        exit;
    }



    // --- MESSENGER: Create Chats ---
    if ($act === 'create_chat') {

        $otherUserId = $input['user_id'];

        $findOther = new ParseQuery('_User');
        try {
            $otherUser = parseRetry(function () use ($findOther, $otherUserId) {
                return $findOther->get($otherUserId);
            });
        } catch (\Parse\ParseException $e) {
            $queryByName = ParseUser::query();
            $queryByName->equalTo('username', $otherUserId);
            $users = parseRetry(function () use ($queryByName) {
                return $queryByName->find();
            });

            if (count($users) === 0) {
                throw new Exception("User not found");
            }
            $otherUser = $users[0];
        }

        $users = [
            $currentUser->getObjectId(),
            $otherUser->getObjectId()
        ];

        $usersHash = makeUsersHash($users);

        // Ищем существующий чат
        $q = new ParseQuery('Chat');
        $q->equalTo('usersHash', $usersHash);
        $existing = parseRetry(function () use ($q) {
            return $q->first();
        });

        if ($existing) {
            echo json_encode([
                'success' => true,
                'chat_id' => $existing->getObjectId()
            ]);
            exit;
        }

        $chatKey = random_bytes(32); // AES-256
        $iv = random_bytes(16);

        $encryptedChatKey = openssl_encrypt(
            $chatKey,
            'AES-256-CBC',
            CHAT_MASTER_KEY,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Создаём новый чат
        $chat = new ParseObject('Chat');
        $chat->setArray('users', [$currentUser, $otherUser]);
        $chat->set('usersHash', $usersHash);

        $chat->set('chatKeyEnc', base64_encode($encryptedChatKey));
        $chat->set('chatKeyIv', base64_encode($iv));


        $chat->set('lastAt', new DateTime());

        $acl = new ParseACL();
        $acl->setPublicReadAccess(false);
        $acl->setReadAccess($currentUser->getObjectId(), true);
        $acl->setReadAccess($otherUser->getObjectId(), true);
        $acl->setWriteAccess($currentUser->getObjectId(), true);
        $acl->setWriteAccess($otherUser->getObjectId(), true);

        $chat->setACL($acl);

        $chat->save();

        echo json_encode([
            'success' => true,
            'chat_id' => $chat->getObjectId()
        ]);
        exit;
    }


    if ($act === 'get_messages') {

        $chatId = $input['chat_id'];

        // Получаем чат
        $chatQuery = new ParseQuery('Chat');
        $chat = parseRetry(function () use ($chatQuery, $chatId) {
            return $chatQuery->get($chatId);
        });

        // Проверяем, что пользователь участник чата
        $users = $chat->get('users');
        $isMember = false;

        foreach ($users as $u) {
            if ($u->getObjectId() === $currentUser->getObjectId()) {
                $isMember = true;
                break;
            }
        }

        if (!$isMember) {
            throw new Exception("Access denied");
        }

        $q = new ParseQuery('ChatState');
        $q->equalTo('chat', $chat);
        $q->equalTo('user', $currentUser);

        $state = parseRetry(function () use ($q) {
            return $q->first();
        });

        if (!$state) {
            $state = new ParseObject('ChatState');
            $state->set('chat', $chat);
            $state->set('user', $currentUser);
            $state->setACL(makeChatMembersAcl($users)); 
        }

        $state->set('lastReadAt', new DateTime());
        $state->set('unreadCount', 0);
        parseRetry(function () use ($state) {
            $state->save();
        });


        $q = new ParseQuery("Message");
        $q->equalTo("chat", $chat);
        $q->descending("createdAt");
        $q->includeKey("sender");
        $q->limit(100);

        $messages = array_reverse(parseRetry(function () use ($q) {
            return $q->find();
        }));

        $result = [];
        foreach ($messages as $msg) {
            $sender = $msg->get('sender');

            $result[] = [
                'id' => $msg->getObjectId(),
                'text' => $msg->get('text'),
                'is_me' => ($sender->getObjectId() === $currentUser->getObjectId()),
                'date' => $msg->getCreatedAt()->getTimestamp()
            ];
        }

        $chatKey = openssl_decrypt(
            base64_decode($chat->get('chatKeyEnc')),
            'AES-256-CBC',
            CHAT_MASTER_KEY,
            OPENSSL_RAW_DATA,
            base64_decode($chat->get('chatKeyIv'))
        );

        echo json_encode([
            'success' => true,
            'messages' => $result,
            'chatKey' => base64_encode($chatKey),
        ]);
        exit;
    }

    if ($act === 'get_chat_key') {

        $chatId = $input['chat_id'];

        // Получаем чат
        $chatQuery = new ParseQuery('Chat');
        $chat = parseRetry(function () use ($chatQuery, $chatId) {
            return $chatQuery->get($chatId);
        });

        // Проверяем, что пользователь участник чата
        $users = $chat->get('users');
        $isMember = false;

        foreach ($users as $u) {
            if ($u->getObjectId() === $currentUser->getObjectId()) {
                $isMember = true;
                break;
            }
        }

        if (!$isMember) {
            throw new Exception("Access denied");
        }

        $chatKey = openssl_decrypt(
            base64_decode($chat->get('chatKeyEnc')),
            'AES-256-CBC',
            CHAT_MASTER_KEY,
            OPENSSL_RAW_DATA,
            base64_decode($chat->get('chatKeyIv'))
        );
        echo json_encode([
            'success' => true,
            'chatKey' => base64_encode($chatKey),
        ]);
        exit;
    }

    // --- MESSENGER: Send Message ---
    if ($act === 'send_message') {

        $text = $input['text'];
        $chatId = $input['chat_id'];

        if (empty(trim($text)))
            throw new Exception("Empty message");


        // Получаем чат
        $chatQuery = new ParseQuery('Chat');
        $chat = parseRetry(function () use ($chatQuery, $chatId) {
            return $chatQuery->get($chatId);
        });

        // Проверка: пользователь участник чата
        $users = $chat->get('users');
        $isMember = false;

        foreach ($users as $u) {
            if ($u->getObjectId() === $currentUser->getObjectId()) {
                $isMember = true;
                break;
            }
        }

        if (!$isMember)
            throw new Exception("Access denied");

        $msg = new ParseObject("Message");
        $msg->set("chat", $chat);
        $msg->set("sender", $currentUser);
        $msg->set("text", $text); // уже зашифрованный текст

        // ACL: доступ всем участникам чата
        $acl = new ParseACL();
        $acl->setPublicReadAccess(false);
        foreach ($users as $u)
            $acl->setReadAccess($u->getObjectId(), true);
        $acl->setWriteAccess($currentUser->getObjectId(), true);

        $msg->setACL($acl);
        $msg->save();


        $chat->set('lastAt', new DateTime());
        $chat->save();

        foreach ($users as $u) {
            if ($u->getObjectId() === $currentUser->getObjectId()) {
                continue;
            }

            $stateQuery = new ParseQuery('ChatState');
            $stateQuery->equalTo('chat', $chat);
            $stateQuery->equalTo('user', $u);
            $otherState = parseRetry(function () use ($stateQuery) {
                return $stateQuery->first();
            });

            if (!$otherState) {
                $otherState = new ParseObject('ChatState');
                $otherState->set('chat', $chat);
                $otherState->set('user', $u);
                $otherState->set('unreadCount', 0);
                $otherState->setACL(makeChatMembersAcl($users));
            }

            $otherState->increment('unreadCount');
            $otherState->save();
        }

        echo json_encode([
            'success' => true,
            'id' => $msg->getObjectId(),
            'date' => time()
        ]);
        exit;
    }


} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
