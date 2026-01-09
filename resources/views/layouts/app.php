<?php

use Din9xtrCloud\ViewModels\LayoutViewModel;

/** @var LayoutViewModel $viewModel */
$page = $viewModel->page;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page->title() ?? 'Cloud App') ?></title>
    <link rel="stylesheet" href=/assets/cloud.css>
</head>
<body>


<?php if ($page->layoutConfig->header === 'default'): ?>
    <header>
        <nav class="navbar">
            <span class="navbar-brand">Cloud Control Panel</span>
            <a href="/" class="back-link">👈 Back</a>

        </nav>
    </header>
<?php else: ?>
    <?php
    $headerFile = __DIR__ . '/../headers/' . $page->layoutConfig->header . '.php';
    if (file_exists($headerFile)):
        include $headerFile;
        ?>
    <?php endif; ?>
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
</body>
</html>