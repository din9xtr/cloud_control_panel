<?php

use Din9xtrCloud\ViewModels\LayoutViewModel;

/** @var LayoutViewModel $viewModel */
$page = $viewModel->page;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

 <title><?= htmlspecialchars($page->title() ?? 'Cloud App') ?></title>
    <!-- Favicon / App icons -->
    <link rel="icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link rel="manifest" href="/manifest.json">

    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#f5f7fa">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0f172a">

    <link rel="stylesheet" href="/assets/cloud.css">

</head>
<body>

<?php if ($page->layoutConfig->header === 'default'): ?>
    <header class="main-header">
        <nav class="navbar">
            <span class="navbar-brand">Cloud Control Panel</span>
            <a href="/" class="back-link">👈 Back</a>

        </nav>
    </header>
<?php elseif ($page->layoutConfig->header !== null): ?>
    <header class="main-header ">
        <?php
        $headerFile = __DIR__ . '/../headers/' . $page->layoutConfig->header . '.php';
        if (file_exists($headerFile)):
            include $headerFile;
            ?>
        <?php endif; ?>
    </header>
<?php endif; ?>

<main class="container">
    <?= $viewModel->content ?>

</main>
<?php if ($page->layoutConfig->showFooter): ?>

    <footer>
        <p>&copy; <?= date('Y') ?> Cloud Control Panel.
            <a href="/license" style="color: #667eea; text-decoration: none; transition: color 0.3s ease;"
               onmouseover="this.style.color='#764ba2'; this.style.textDecoration='underline'"
               onmouseout="this.style.color='#667eea'; this.style.textDecoration='none'">
                MIT License
            </a>
        </p>
    </footer>
<?php endif; ?>
<script src="/js/burger.js"></script>
</body>
</html>
