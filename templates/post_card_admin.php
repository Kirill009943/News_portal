<?php
$author = $post->get('author');
$authorName = $author ? $author->get('username') : 'Unknown';
$postId = $post->getObjectId();
?>
<article class="admin-post-card">
    <div class="admin-post-body">
        <div class="post-meta">
            <span><?php echo e('author'); ?>: <?php echo htmlspecialchars($authorName); ?></span>
            <span><?php echo formatDate($post->getCreatedAt()); ?></span>
        </div>

        <h3 class="post-title">
            <a href="post.php?id=<?php echo $postId; ?>">
                <?php echo htmlspecialchars($post->get('title')); ?>
            </a>
        </h3>

        <div class="post-text">
            <?php echo nl2br(htmlspecialchars(mb_substr($post->get('content'), 0, 220) . '...')); ?>
        </div>
    </div>

    <div class="admin-actions">
        <form action="api/action.php" method="POST">
            <input type="hidden" name="act" value="approve">
            <input type="hidden" name="id" value="<?php echo $postId; ?>">
            <button type="submit" class="admin-btn approve-btn"><?php echo e('approve'); ?></button>
        </form>

        <form action="api/action.php" method="POST" class="reject-form">
            <input type="hidden" name="act" value="reject">
            <input type="hidden" name="id" value="<?php echo $postId; ?>">
            <input type="text" name="reason" placeholder="<?php echo e('reject_reason'); ?>" required>
            <button type="submit" class="admin-btn reject-btn"><?php echo e('reject'); ?></button>
        </form>
    </div>
</article>
