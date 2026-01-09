<?php
/** @var Din9xtrCloud\ViewModels\Login\LoginViewModel $viewModel */

?>
<div class="login-card">
    <h1><?= htmlspecialchars($viewModel->title ?? 'Login') ?></h1>
    <?php if (!empty($viewModel->error)) : ?>
        <p class="error"><?= htmlspecialchars($viewModel->error) ?></p>
    <?php endif; ?>
    <form method="POST" action="/login" class="login-form">
        <label>
            <input type="text" name="username" required placeholder="Enter username">
        </label>
        <label>
            <input type="password" name="password" required placeholder="Enter password">
        </label>
        <button type="submit">Sign In</button>
        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($viewModel->csrf) ?>">
    </form>
</div>
