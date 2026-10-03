<?php
$author = $post->get('author');
$authorName = $author ? $author->get('username') : 'Unknown';
$score = $post->get('score') ?? 0;
$likes = $post->get('likes') ?? [];
$dislikes = $post->get('dislikes') ?? [];
$userLiked = $currentUser && in_array($currentUser->getObjectId(), $likes);
$userDisliked = $currentUser && in_array($currentUser->getObjectId(), $dislikes);

$likeStyle = $userLiked ? 'color:#ff4500' : '';
$dislikeStyle = $userDisliked ? 'color:#7193ff' : '';

$isFavorite = isset($_SESSION['favorites'][$post->getObjectId()]);
$favText = $isFavorite ? t('fav_in') : t('fav_add');
$favStyle = $isFavorite ? 'color:gold;font-weight:bold;' : ''; // подсветка желтым
?>
<div class="card d-f p-2">
    <div class="vote-column">
        <button onclick="vote('<?php echo $post->getObjectId(); ?>', 'up', this)" class="vote-btn"
            style="background:none; border:none; cursor:pointer; font-size:16px; <?php echo $likeStyle; ?>">
            ▲
        </button>

        <div class="vote-score" style="text-align:center; font-weight:bold;">
            <?php echo $score; ?>
        </div>

        <button onclick="vote('<?php echo $post->getObjectId(); ?>', 'down', this)" class="vote-btn"
            style="background:none; border:none; cursor:pointer; font-size:16px; <?php echo $dislikeStyle; ?>">
            ▼
        </button>
    </div>
    <div class="content-column">
        <div class="post-meta">
            <span><?php echo e('author'); ?>: <?php echo htmlspecialchars($authorName); ?></span>
            <span>• <?php echo formatDate($post->getCreatedAt()); ?></span>
        </div>
        <h3 class="post-title"><a href="post.php?id=<?php echo $post->getObjectId(); ?>"
                style="text-decoration:none; color:inherit;"><?php echo htmlspecialchars($post->get('title')); ?></a>
        </h3>
        <div class="post-text">
            <?php echo nl2br(htmlspecialchars(mb_substr($post->get('content'), 0, 150) . '...')); ?>
        </div>

        <div class="post-actions">
            <a href="post.php?id=<?php echo $post->getObjectId(); ?>" class="action-btn"><?php echo e('comments'); ?></a>

            <button onclick="toggleFavorite('<?php echo $post->getObjectId(); ?>', this)" class="action-btn" style="<?php echo $favStyle; ?>"><?php echo htmlspecialchars($favText); ?></button>

            <button class="action-btn" onclick="sharePost('<?php echo $post->getObjectId(); ?>')"><?php echo e('share'); ?></button>
        </div>
    </div>
</div>
