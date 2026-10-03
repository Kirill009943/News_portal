<?php
require dirname(__DIR__) . '/src/bootstrap.php';
?>

<!DOCTYPE html>
<html lang="<?php echo LANG_CODE; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo e('settings'); ?></title>
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

<div class="container single-col">
    <div class="card p-2">
        <h3><?php echo e('appearance'); ?></h3>
        
        <label><?php echo e('theme'); ?></label>
        <select id="theme-select" class="input-field">
            <option value="light"><?php echo e('theme_light'); ?></option>
            <option value="dark"><?php echo e('theme_dark'); ?></option>
        </select>

        <label><?php echo e('font_size'); ?></label>
        <select id="size-select" class="input-field">
            <option value="12px"><?php echo e('size_small'); ?></option>
            <option value="14px"><?php echo e('size_normal'); ?></option>
            <option value="18px"><?php echo e('size_large'); ?></option>
        </select>

        <label><?php echo e('language'); ?></label>
        <select id="lang-select" class="input-field">
            <option value="ru" <?php echo LANG_CODE === 'ru' ? 'selected' : ''; ?>>Русский</option>
            <option value="en" <?php echo LANG_CODE === 'en' ? 'selected' : ''; ?>>English</option>
        </select>

        <button class="btn btn-primary" onclick="saveSettings()"><?php echo e('save_settings'); ?></button>
    </div>
</div>

<script src="assets/js/script.js"></script>
<script>
    // Загрузка текущих значений
    document.getElementById('theme-select').value = localStorage.getItem('theme') || 'light';
    document.getElementById('size-select').value = localStorage.getItem('textSize') || '14px';

    function saveSettings() {
        const theme = document.getElementById('theme-select').value;
        const size = document.getElementById('size-select').value;
        const lang = document.getElementById('lang-select').value;
        const currentLang = "<?php echo LANG_CODE; ?>";
        
        localStorage.setItem('theme', theme);
        localStorage.setItem('textSize', size);
        
        applySettings(); // Применить мгновенно

        if (lang !== currentLang) {
            document.cookie = 'lang=' + lang + ';path=/;max-age=31536000;samesite=lax';
            location.reload();
        }
    }
</script>
</body>
</html>