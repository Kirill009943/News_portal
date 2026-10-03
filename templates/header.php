<header>
    <div class="logo"><a href="index.php" style="margin:0; text-decoration:none; color:inherit;">News</a></div>
    <nav id="main-nav">
        <a href="index.php"><?php echo e('nav_feed'); ?></a>
        <?php if ($currentUser): ?>
            <a href="messenger.php"><?php echo e('nav_messenger'); ?></a>
            <!-- <a href="notifications.php">Уведомления</a> -->
            <a href="favorites.php"><?php echo e('nav_favorites'); ?></a>
            <a href="settings.php"><?php echo e('nav_settings'); ?></a>
            <?php if ($currentUser->get('role') === 'admin'): ?>
                <a href="admin.php" style="color:#ff4500"><?php echo e('nav_admin'); ?></a>
            <?php endif; ?>
            <a href="profile.php">👤 <?php echo htmlspecialchars($currentUser->get('username')); ?></a>
            <a href="api/action.php?act=logout"><?php echo e('nav_logout'); ?></a>
        <?php else: ?>
            <a href="profile.php"><?php echo e('nav_login'); ?></a>
            <a href="settings.php"><?php echo e('nav_settings'); ?></a>
        <?php endif; ?>
    </nav>
</header>
