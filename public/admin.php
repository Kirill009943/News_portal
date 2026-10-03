<?php
require dirname(__DIR__) . '/src/bootstrap.php';

if (!$currentUser || $currentUser->get('role') !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo LANG_CODE; ?>">

<head>
    <meta charset="UTF-8">
    <title><?php echo e('admin_panel'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-theme');
        });
    </script>
</head>

<body>
    <?php include dirname(__DIR__) . '/templates/header.php'; ?>

    <div class="container admin-container">
        <main class="admin-main">
            <div class="admin-header">
                <div>
                    <div class="admin-kicker">Moderation</div>
                    <h1><?php echo e('check_posts'); ?></h1>
                </div>
                <a class="admin-refresh" href="admin.php"><?php echo e('refresh'); ?></a>
            </div>

            <div class="admin-list">
                <?php
                $_GET['skip'] = 0;
                $_GET['sort'] = "new";
                $_GET['limit'] = 100;
                $_GET['pageload'] = "admin";
                include __DIR__ . '/api/load_posts.php';
                ?>
            </div>
        </main>
    </div>
</body>

</html>
