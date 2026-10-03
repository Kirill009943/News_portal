<?php
require dirname(__DIR__) . '/src/bootstrap.php';
$sortBy = $_GET['sort'] ?? 'new';
?>

<!DOCTYPE html>
<html lang="<?php echo LANG_CODE; ?>">

<head>
    <meta charset="UTF-8">
    <title>News Feed</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-theme');
            document.body.style.fontSize = localStorage.getItem('textSize') || '14px';
        });
    </script>
</head>

<body>
    <?php include dirname(__DIR__) . '/templates/header.php'; ?>
    <div class="container">
        <main id="feed-container">
            <div id="posts-wrapper">
                <?php
                $_GET['skip'] = 0;
                $_GET['sort'] = $sortBy;
                $_GET['limit'] = 10;
                include __DIR__ . '/api/load_posts.php';
                ?>
            </div>
            <div id="loader" style="text-align:center; padding:20px; display:none;">
                <?php echo e('loading'); ?>
            </div>
            <div id="scroll-trigger" style="height: 10px;"></div>
        </main>
        <aside>
            <div class="sort-bar" style="flex-wrap: wrap;">
                <a href="?sort=hot" class="sort-btn <?php echo $sortBy == 'hot' ? 'active' : ''; ?>"><?php echo e('sort_hot'); ?></a>
                <a href="?sort=popular" class="sort-btn <?php echo $sortBy == 'popular' ? 'active' : ''; ?>"><?php echo e('sort_popular'); ?></a>
                <a href="?sort=new" class="sort-btn <?php echo $sortBy == 'new' ? 'active' : ''; ?>"><?php echo e('sort_new'); ?></a>
                <a href="?sort=old" class="sort-btn <?php echo $sortBy == 'old' ? 'active' : ''; ?>"><?php echo e('sort_old'); ?></a>
            </div>

            <div class="sidebar-card">
                <div class="sidebar-title"><?php echo e('community'); ?></div>
                <p style="font-size:13px; margin-bottom:10px;"><?php echo e('welcome_text'); ?></p>
                <a href="profile.php" class="btn-primary"
                    style="display:block; text-align:center; text-decoration:none;"><?php echo e('create_post'); ?></a>
            </div>
        </aside>
    </div>
    <script>
        const currentSort = "<?php echo $sortBy; ?>";
        window.i18n = <?php echo langJson(); ?>;
    </script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/ajax.js"></script>
</body>

</html>