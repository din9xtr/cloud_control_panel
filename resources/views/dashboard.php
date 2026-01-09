<?php
/** @var Din9xtrCloud\ViewModels\Dashboard\DashboardViewModel $viewModel */
?>
<div class="dashboard-container">


    <!-- Основная статистика хранения -->
    <section class="storage-overview">
        <div class="section-header">
            <h2 class="section-title">Storage Overview</h2>
            <div class="storage-summary">
                <span class="summary-text">Total: <strong><?= $viewModel->stats['storage']['total'] ?></strong></span>
                <span class="summary-text">Used: <strong><?= $viewModel->stats['storage']['used'] ?></strong></span>
                <span class="summary-text">Free: <strong><?= $viewModel->stats['storage']['free'] ?></strong></span>
            </div>
        </div>

        <!-- Прогресс-бар общего использования -->
        <div class="main-progress-container">
            <div class="progress-header">
                <span class="progress-label">Overall Usage</span>
                <span class="progress-percent"><?= $viewModel->stats['storage']['percent'] ?>%</span>
            </div>
            <div class="main-progress-bar">
                <div class="main-progress-fill" style="width: <?= $viewModel->stats['storage']['percent'] ?>%"></div>
            </div>
            <div class="progress-details">
                <span class="detail-item">Used: <?= $viewModel->stats['storage']['used'] ?></span>
                <span class="detail-item">Available: <?= $viewModel->stats['storage']['free'] ?></span>
                <span class="detail-item">Total: <?= $viewModel->stats['storage']['total'] ?></span>
            </div>
        </div>
    </section>

    <!-- Детальная статистика по типам файлов -->
    <section class="stats-grid">
        <?php foreach ($viewModel->stats['storage']['folders'] as $folder): ?>
            <a href="/folders/<?= urlencode($folder['name']) ?>" class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <span>📁</span>
                </div>

                <div class="stat-content">
                    <h3 class="stat-title"><?= htmlspecialchars($folder['name']) ?></h3>

                    <div class="stat-value"><?= $folder['size'] ?></div>

                    <div class="stat-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $folder['percent'] ?>%"></div>
                        </div>
                        <span class="progress-text"><?= $folder['percent'] ?>% of total</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </section>


    <!-- Быстрые действия -->
    <section class="quick-actions">
        <h2 class="actions-title">Storage Management</h2>
        <div class="actions-grid">
            <button class="action-btn" id="upload-file-btn">
                <span class="action-icon">📤</span>
                <span class="action-text">Upload Files</span>
            </button>
            <button class="action-btn" id="create-folder-btn">
                <span class="action-icon">📁</span>
                <span class="action-text">Create Folder</span>
            </button>
            <!--            <button class="action-btn">-->
            <!--                <span class="action-icon">🧹</span>-->
            <!--                <span class="action-text">Clean Storage</span>-->
            <!--            </button>-->
            <!--            <button class="action-btn">-->
            <!--                <span class="action-icon">📊</span>-->
            <!--                <span class="action-text">Generate Report</span>-->
            <!--            </button>-->
        </div>
    </section>

    <!-- Предупреждения и уведомления -->
    <!--    <section class="alerts-container">-->
    <!--        <div class="section-header">-->
    <!--            <h2 class="section-title">Storage Alerts</h2>-->
    <!--        </div>-->
    <!--                <div class="alerts-list">-->
    <!--                    <div class="alert-item warning">-->
    <!--                        <div class="alert-icon">⚠️</div>-->
    <!--                        <div class="alert-content">-->
    <!--                            <h4>Storage nearing capacity</h4>-->
    <!--                            <p>You've used 65% of your available storage. Consider upgrading your plan.</p>-->
    <!--                        </div>-->
    <!--                        <button class="alert-action">Review</button>-->
    <!--                    </div>-->
    <!--        <div class="alert-item info">-->
    <!--            <div class="alert-icon">ℹ️</div>-->
    <!--            <div class="alert-content">-->
    <!--                <h4>Backup scheduled</h4>-->
    <!--                <p>Next backup: Today at 2:00 AM</p>-->
    <!--            </div>-->
    <!--            <button class="alert-action">Settings</button>-->
    <!--        </div>-->
    <!--    </section>-->
</div>


<!-- Модальное окно - скрыто по умолчанию -->
<div class="modal-overlay" id="create-folder-modal">
    <div class="modal">
        <h3>Create new folder</h3>

        <form method="POST" action="/storage/folders">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($viewModel->csrf) ?>">

            <div class="form-group">
                <label>Folder name</label>
                <label>
                    <input
                            type="text"
                            name="name"
                            required
                            pattern="[a-zA-Z\u0400-\u04FF0-9_\- ]+"
                    >
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancel-create-folder">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="upload-file-modal">
    <div class="modal">
        <h3>Upload File</h3>

        <form method="POST" action="/storage/files" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($viewModel->csrf) ?>">

            <div class="form-group">
                <label>Select folder</label>
                <label>
                    <select name="folder" required class="folder-select">
                        <option value="" disabled selected>Choose folder...</option>
                        <?php foreach ($viewModel->stats['storage']['folders'] as $folder): ?>
                            <option value="<?= htmlspecialchars($folder['name']) ?>">
                                <?= htmlspecialchars($folder['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="form-group">
                <label>Choose file</label>
                <input type="file" name="file" required accept="*/*" class="file-input">
                <div class="file-preview" style="display:none; margin-top:10px;">
                    <img src="" alt="Preview" style="max-width:100px; max-height:100px; display:none;">
                    <div class="file-info"></div>
                </div>
            </div>

            <div class="form-group">
                <div class="upload-progress" style="display:none;">
                    <div class="progress-bar" style="height:6px; background:#e2e8f0; border-radius:3px;">
                        <div class="progress-fill"
                             style="height:100%; background:#667eea; width:0; border-radius:3px;"></div>
                    </div>
                    <div class="progress-text" style="font-size:0.9rem; color:#718096; margin-top:5px;"></div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancel-upload-file">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="submit-upload">
                    Upload
                </button>
            </div>
        </form>
    </div>
</div>
<script src="/js/tus.js"></script>
<script src="/js/shared.js"></script>
<script src="/js/dashboard.js"></script>