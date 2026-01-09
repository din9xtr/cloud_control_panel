<?php
/** @var Din9xtrCloud\ViewModels\Errors\ErrorViewModel $viewModel */
?>

<div class="error-container">
    <div class="error-code"><?= htmlspecialchars($viewModel->errorCode) ?></div>

    <p class="error-message">
        <?= nl2br(htmlspecialchars($viewModel->message)) ?>
    </p>


    <div class="action-buttons">
        <a href="javascript:history.back()" class="btn btn-secondary">
            Go Back
        </a>
        <a href="/" class="btn btn-primary">
            Go to Homepage
        </a>
    </div>
</div>