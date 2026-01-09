<?php
/** @var Din9xtrCloud\ViewModels\Folder\FolderViewModel $viewModel */
?>
<div class="folder-container">

    <!-- Действия с файлами -->
    <section class="file-actions-section">
        <h2 class="section-title">Files</h2>
        <div class="action-buttons">
            <button class="btn btn-primary" id="upload-file-folder">
                <span class="btn-icon">📤</span>Upload File
            </button>
            <form method="GET" action="/storage/files/download/multiple" class="multiple-download-form"
                  style="display: none;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($viewModel->csrf) ?>">
                <input type="hidden" name="folder" value="<?= htmlspecialchars($viewModel->title) ?>">
                <input type="hidden" name="file_names" id="multiple-download-names" value="">
                <button type="submit" class="btn btn-success" id="download-multiple-btn">
                    <span class="btn-icon">⬇️</span>Download Selected
                </button>
            </form>
            <form method="POST" action="/storage/files/delete/multiple" class="multiple-delete-form"
                  style="display: none;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($viewModel->csrf) ?>">
                <input type="hidden" name="folder" value="<?= htmlspecialchars($viewModel->title) ?>">
                <input type="hidden" name="file_names" id="multiple-file-names" value="">
                <button type="submit" class="btn btn-danger" id="delete-multiple-btn">
                    <span class="btn-icon">🗑️</span>Delete Selected
                </button>
            </form>
            <div class="selection-controls">
                <label class="checkbox-label">
                    <input type="checkbox" id="select-all-checkbox">
                    <span class="checkmark"></span>
                    Select All
                </label>
            </div>
        </div>
    </section>

    <!-- Список файлов -->
    <section class="files-section">
        <?php if (empty($viewModel->files)): ?>
            <div class="empty-state">
                <div class="empty-icon">📁</div>
                <h3>No files in this folder</h3>
                <p>Upload your first file to get started</p>
                <button class="btn btn-primary" id="upload-first-file">
                    <span class="btn-icon">📤</span>Upload File
                </button>
            </div>
        <?php else: ?>
            <div class="files-grid">
                <?php foreach ($viewModel->files as $file): ?>
                    <div class="file-card" data-file-name="<?= htmlspecialchars($file['name']) ?>">
                        <div class="file-card-header">
                            <label class="file-checkbox">
                                <input type="checkbox" class="file-select-checkbox"
                                       value="<?= htmlspecialchars($file['name']) ?>">
                            </label>
                            <div class="file-icon">
                                <?php
                                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $icon = match (strtolower($extension)) {
                                    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => '🖼️',
                                    'pdf' => '📕',
                                    'doc', 'docx' => '📄',
                                    'xls', 'xlsx' => '📊',
                                    'zip', 'rar', '7z', 'tar', 'gz' => '📦',
                                    'mp3', 'wav', 'ogg' => '🎵',
                                    'mp4', 'avi', 'mkv', 'mov' => '🎬',
                                    default => '📄'
                                };
                                echo $icon;
                                ?>
                            </div>
                        </div>

                        <div class="file-info">
                            <h4 class="file-name" title="<?= htmlspecialchars($file['name']) ?>">
                                <?= htmlspecialchars($file['name']) ?>
                            </h4>

                            <div class="file-details">
                                <div class="detail-item">
                                    <span class="detail-label">Size:</span>
                                    <span class="detail-value"><?= $file['size'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Modified:</span>
                                    <span class="detail-value"><?= $file['modified'] ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="file-card-actions">
                            <a href="/storage/files/download?file=<?= urlencode($file['name']) ?>&folder=<?= urlencode($viewModel->title) ?>"
                               class="file-action-btn download-btn"
                               title="Download">
                                <span class="action-icon">🤏</span>
                            </a>
                            <form method="POST" action="/storage/files/delete"
                                  class="delete-form"
                                  onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars($file['name']) ?>?')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($viewModel->csrf) ?>">
                                <input type="hidden" name="file_name" value="<?= htmlspecialchars($file['name']) ?>">
                                <input type="hidden" name="folder"
                                       value="<?= htmlspecialchars($viewModel->title) ?>">
                                <button type="submit" class="file-action-btn delete-btn" title="Delete">
                                    <span class="action-icon">🗑️</span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Модальное окно загрузки файла -->
<div class="modal-overlay" id="upload-file-modal-folder">
    <div class="modal">
        <h3>Upload File to <?= htmlspecialchars($viewModel->folderName) ?></h3>

        <form method="POST" action="/storage/files" enctype="multipart/form-data" id="upload-form-folder">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($viewModel->csrf) ?>">
            <input type="hidden" name="folder" value="<?= htmlspecialchars($viewModel->title) ?>">

            <div class="form-group">
                <label>Choose file</label>
                <input type="file" name="file" required accept="*/*" class="file-input" id="file-input-folder">
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
                <button type="button" class="btn btn-secondary" id="cancel-upload-file-folder">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="submit-upload-folder">
                    Upload
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/js/tus.js"></script>
<script src="/js/shared.js"></script>
<script src="/js/folder.js"></script>
