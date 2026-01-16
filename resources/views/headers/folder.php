<?php /** @var Din9xtrCloud\ViewModels\Folder\FolderViewModel $page */
?>
<div class="folder-info">
    <h1 class="folder-title">
        <span class="folder-icon">📁</span>
        <?= htmlspecialchars($page->title) ?>
    </h1>
    <div class="folder-stats">
        <span class="stat-item"><?= count($page->files) ?> files</span>
        <span class="stat-separator">•</span>
        <span class="stat-item"><?= $page->totalSize ?></span>
        <span class="stat-separator">•</span>
        <span class="stat-item">Last updated: <?= $page->lastModified ?></span>
    </div>
</div>
<div class="folder-actions">
    <?php if ($page->title !== 'documents' && $page->title !== 'media'): ?>
        <form method="POST" action="/storage/folders/<?= urlencode($page->title) ?>/delete" style="display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($page->csrf) ?>">
            <button type="submit" class="btn btn-danger"
                    onclick="return confirm('Delete folder <?= htmlspecialchars($page->title) ?>?');">
                <span class="btn-icon">🗑️</span>Delete Folder
            </button>
        </form>
    <?php endif; ?>

    <a href="/" class="btn btn-secondary">
        <span class="btn-icon">👈</span>Back to Dashboard
    </a>
</div>
