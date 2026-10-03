<?php
require dirname(__DIR__) . '/src/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="<?php echo LANG_CODE; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo e('profile'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if(localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-theme');
        });
    </script>
</head>
<body>
<?php include dirname(__DIR__) . '/templates/header.php'; ?>

<div class="container">
    <main>
        <?php if (!$currentUser): ?>
        <div id="auth-panel" class="sidebar-card">
            <h2><?php echo e('login'); ?></h2>
            <form action="api/action.php" method="POST">
                <input type="hidden" name="act" value="login">
                <input type="text" name="username" class="input-field" placeholder="<?php echo e('username'); ?>" required>
                <input type="password" name="password" class="input-field" placeholder="<?php echo e('password'); ?>" required>
                <button type="submit" class="btn-primary"><?php echo e('sign_in'); ?></button>
            </form>
            
            <hr>
            
            <h2><?php echo e('registration'); ?></h2>
            <form action="api/action.php" method="POST">
                <input type="hidden" name="act" value="register">
                <input type="text" name="username" class="input-field" placeholder="<?php echo e('username'); ?>" required>
                <input type="password" name="password" class="input-field" placeholder="<?php echo e('password'); ?>" required>
                <button type="submit" class="action-btn"><?php echo e('sign_up'); ?></button>
            </form>
        </div>

        <?php else: ?>
        <div id="user-panel">
            <div class="sidebar-card">
                <h2><?php echo e('create_post'); ?></h2>
                <form action="api/action.php" method="POST">
                    <input type="hidden" name="act" value="create_post">
                    <input type="text" name="title" class="input-field" placeholder="<?php echo e('post_title'); ?>" required>
                    <textarea name="content" class="input-field" rows="4" placeholder="<?php echo e('post_text'); ?>" required></textarea>
                    <button type="submit" class="btn-primary"><?php echo e('publish'); ?></button>
                </form>
            </div>
            
            <h3><?php echo e('my_posts'); ?></h3>
            <?php
                $_GET['skip'] = 0;
                $_GET['sort'] = "new";
                $_GET['limit'] = 10;
                $_GET['pageload'] = "profile";
                include __DIR__ . '/api/load_posts.php';
                ?>
        </div>
        <?php endif; ?>
    </main>
</div>
<script>window.i18n = <?php echo langJson(); ?>;</script>
<script src="assets/js/script.js"></script>
<script src="assets/js/ajax.js"></script>
</body>
</html>