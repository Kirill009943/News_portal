<?php
require dirname(__DIR__) . '/src/bootstrap.php';

if (!$currentUser) {
    header("Location: profile.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo LANG_CODE; ?>">

<head>
    <meta charset="UTF-8">
    <title><?php echo e('favorites'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-theme');
        });
    </script>
</head>

<body>
    <?php include dirname(__DIR__) . '/templates/header.php'; ?>
    <div class="container">
        <main>
            <h3><?php echo e('favorites'); ?></h3>
            <?php
            $_GET['skip'] = 0;
            $_GET['sort'] = "new";
            $_GET['limit'] = 25;
            $_GET['pageload'] = "favorites";
            include __DIR__ . '/api/load_posts.php';
            ?>

        </main>
    </div>
</body>
<script>window.i18n = <?php echo langJson(); ?>;</script>
<script src="assets/js/script.js"></script>
<script src="assets/js/ajax.js"></script>
</html> 