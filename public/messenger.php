<?php
require dirname(__DIR__) . '/src/bootstrap.php';
use Parse\ParseUser;
use Parse\ParseQuery;

if (!$currentUser) {
    header("Location: profile.php"); // Редирект если не залогинен
    exit;
}

$q = new ParseQuery('Chat');
$q->containedIn('users', [$currentUser]);
$q->descending('lastAt');
$q->includeKey('users');

try {
    $chats = parseRetry(function () use ($q) {
        return $q->find();
    });
} catch (Exception $e) {
    $chats = [];
    $chatLoadError = true;
}

?>

<!DOCTYPE html>
<html lang="<?php echo LANG_CODE; ?>">

<head>
    <meta charset="UTF-8">
    <title><?php echo e('messenger'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/parse@8.0.0/dist/parse.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-theme');
            document.body.style.fontSize = localStorage.getItem('textSize') || '14px';
        });
    </script>
</head>

<body>
    <?php include dirname(__DIR__) . '/templates/header.php'; ?>

    <div class="container" style="max-width: 1100px; grid-template-columns: 1fr;">
        <div class="messenger-container">
            <aside class="users-list">
    <div class="user-search-bar">
        <input type="text" id="user-find-input" placeholder="<?php echo e('find_user'); ?>">
        <button class="btn btn-primary" onclick="createChat()"><?php echo e('find'); ?></button>
    </div>

    <?php if (!empty($chatLoadError)): ?>
        <div class="messenger-warning"><?php echo e('chats_load_error'); ?></div>
    <?php endif; ?>

    <?php foreach ($chats as $chat): ?>
        <?php
        $otherUser = null;
        foreach ($chat->get('users') as $u) {
            if ($u->getObjectId() !== $currentUser->getObjectId()) {
                $otherUser = $u;
                break;
            }
        }

        $stateQuery = new ParseQuery('ChatState');
        $stateQuery->equalTo('user', $currentUser);
        $stateQuery->equalTo('chat', $chat);
        try {
            $states = parseRetry(function () use ($stateQuery) {
                return $stateQuery->find();
            });
        } catch (Exception $e) {
            $states = [];
        }

        $unread = 0; 
        foreach ($states as $state) {
            $unread = $state->get('unreadCount');
        }

        if (!$otherUser)
            continue;

        $otherName = $otherUser->get('username');
        $otherNameJs = htmlspecialchars(json_encode($otherName), ENT_QUOTES);
        ?>

        <div class="user-item"
            onclick='selectChat(<?php echo json_encode($chat->getObjectId()); ?>, <?php echo $otherNameJs; ?>, this)'>
            <div
                style="width:30px; height:30px; background:#ccc; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#333;">
                <?php echo htmlspecialchars(mb_strtoupper(mb_substr($otherName, 0, 1))); ?>
            </div>
            <div><?php echo htmlspecialchars($otherName); ?></div>
            <div style="color:red;margin-left:auto;" class="badge-<?php echo $chat->getObjectId(); ?>">
                <?php if ($unread > 0)
                    echo $unread; ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (count($chats) == 0): ?>
        <div style="padding:20px; color:gray; text-align:center;"><?php echo e('no_chats'); ?></div>
    <?php endif; ?>
</aside>

            <main class="chat-area">
                <div class="chat-header" id="chat-header"><?php echo e('choose_contact'); ?></div>

                <div class="messages-box" id="messages-box">
                    <div style="text-align:center; margin-top:50px; color:gray;">
                        <?php echo e('start_dialog'); ?>
                    </div>
                </div>

                <div class="chat-input-area" id="input-area" style="display:none;">
                    <input type="text" id="msg-input" placeholder="<?php echo e('write_message'); ?>"
                        onkeypress="handleEnter(event)">
                    <button class="btn btn-primary" onclick="sendMessage()"><?php echo e('send'); ?></button>
                </div>
            </main>
        </div>
    </div>
    <script src="assets/js/crypt.js"></script>
    <script>
        window.currentUserObjectId = <?php echo json_encode($currentUser->getObjectId()); ?>;
        window.i18n = <?php echo langJson(); ?>;
        window.langLocale = <?php echo json_encode(langLocale()); ?>;
    </script>
    <script src="assets/js/messenger.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>
