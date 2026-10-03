<?php
require dirname(__DIR__) . '/src/bootstrap.php';
use Parse\ParseQuery;
use Parse\ParseObject;

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

try {
    $q = new ParseQuery("Article");
    $q->includeKey("author");
    $post = $q->get($id);
    $score = $post->get('score') ?? 0;
    $likes = $post->get('likes') ?? [];
    $dislikes = $post->get('dislikes') ?? [];
    $userLiked = $currentUser && in_array($currentUser->getObjectId(), $likes);
    $userDisliked = $currentUser && in_array($currentUser->getObjectId(), $dislikes);


    $likeStyle = $userLiked ? 'color:#ff4500;' : '';
    $dislikeStyle = $userDisliked ? 'color:#7193ff;' : '';

    // Увеличить просмотры
    $post->increment("views");
    $post->save();

    // Загрузка комментариев
    $cQuery = new ParseQuery("Comment");
    $cQuery->equalTo("article", $post);
    $cQuery->includeKey("author");
    $cQuery->descending("createdAt");
    $comments = $cQuery->find();

} catch (Exception $e) {
    echo t('post_not_found');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo LANG_CODE; ?>">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post->get('title')); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-theme');
        });
    </script>
</head>

<body>
    <?php include dirname(__DIR__) . '/templates/header.php'; ?>

    <div class="container single-col full-post-view">
        <div class="card post-card">
            <div class="vote-column">
                <button onclick="vote('<?php echo $post->getObjectId(); ?>', 'up', this)" class="vote-btn"
                    style="background:none; border:none; cursor:pointer; font-size:20px; <?php echo $likeStyle; ?>">
                    ▲
                </button>

                <div class="vote-score" style="text-align:center; font-weight:bold;">
                    <?php echo $score; ?>
                </div>

                <button onclick="vote('<?php echo $post->getObjectId(); ?>', 'down', this)" class="vote-btn"
                    style="background:none; border:none; cursor:pointer; font-size:20px; <?php echo $dislikeStyle; ?>">
                    ▼
                </button>
            </div>

            <div class="content-column p-2">
                <h1 style="font-size: 2em;"><?php echo htmlspecialchars($post->get('title')); ?></h1>
                <div style="color: var(--text-meta); margin-bottom: 20px;">
                    <?php echo e('author'); ?>: <?php echo htmlspecialchars($post->get('author')->get('username')); ?> |
                    <?php echo e('views'); ?>: <?php echo $post->get('views'); ?>
                </div>

                <div style="white-space: pre-wrap;"><?php echo htmlspecialchars($post->get('content')); ?></div>

                <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">

                <h3><?php echo e('comments'); ?></h3>
                <?php if ($currentUser): ?>
                    <form onsubmit="sendComment(event, '<?php echo $post->getObjectId(); ?>')">
                        <textarea name="text" class="input-field" rows="3" placeholder="<?php echo e('write_comment'); ?>"
                            required></textarea>
                        <button type="submit" class="btn btn-primary" style="margin-top:10px;"><?php echo e('send'); ?></button>
                    </form>
                <?php else: ?>
                    <p><a href="profile.php"><?php echo e('login_to_comment'); ?></a></p>
                <?php endif; ?>

                <div class="comments-container" style="margin-top: 20px;">
                    <?php foreach ($comments as $c): ?>
                        <div style="border-bottom:1px solid var(--border); padding:10px 0;">
                            <b><?php echo htmlspecialchars($c->get('author')->get('username')); ?>:</b>
                            <?php echo htmlspecialchars($c->get('text')); ?>
                            <div style="font-size:0.8em; color:gray"><?php echo formatDate($c->getCreatedAt()); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <script>window.i18n = <?php echo langJson(); ?>;</script>
    <script src="assets/js/ajax.js"></script>
</body>

</html>