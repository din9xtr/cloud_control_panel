(function () {
    'use strict';

    const {modalManager, FileUploader, showNotification} = window.sharedUtils;

    /* =======================
     * SELECT/DELETE FILES LOGIC
     * ======================= */
    class FileSelectionManager {
        constructor() {
            this.selectAllCheckbox = document.getElementById('select-all-checkbox');
            this.fileCheckboxes = document.querySelectorAll('.file-select-checkbox');
            this.fileCards = document.querySelectorAll('.file-card');
            this.deleteMultipleForm = document.querySelector('.multiple-delete-form');
            this.downloadMultipleForm = document.querySelector('.multiple-download-form');
            this.deleteMultipleBtn = document.getElementById('delete-multiple-btn');
            this.downloadMultipleBtn = document.getElementById('download-multiple-btn');
            this.multipleFileNamesInput = document.getElementById('multiple-file-names');
            this.multipleDownloadNamesInput = document.getElementById('multiple-download-names');

            this.init();
        }

        init() {
            if (this.selectAllCheckbox) {
                this.selectAllCheckbox.addEventListener('change', () => this.toggleSelectAll());
            }

            this.fileCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => this.updateSelectionState());
            });

            this.fileCards.forEach(card => {
                card.addEventListener('click', (e) => this.handleCardClick(e, card));
            });

            if (this.deleteMultipleForm) {
                this.deleteMultipleForm.addEventListener('submit', (e) => this.handleMultipleDelete(e));
            }

            if (this.downloadMultipleForm) {
                this.downloadMultipleForm.addEventListener('submit', (e) => this.handleMultipleDownload(e));
            }
        }

        handleCardClick(e, card) {
            if (e.target.closest('.file-card-actions') ||
                e.target.closest('.file-action-btn') ||
                e.target.closest('.delete-form') ||
                e.target.closest('a')) {
                return;
            }

            const checkbox = card.querySelector('.file-select-checkbox');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        }

        toggleSelectAll() {
            const isChecked = this.selectAllCheckbox.checked;
            this.fileCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                checkbox.dispatchEvent(new Event('change'));
            });
            this.updateSelectionState();
        }

        updateSelectionState() {
            const selectedFiles = Array.from(this.fileCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            // flash selected
            this.fileCards.forEach(card => {
                const fileName = card.dataset.fileName;
                if (selectedFiles.includes(fileName)) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });

            // show/hide action-buttons
            if (selectedFiles.length > 0) {
                this.deleteMultipleForm.style.display = 'block';
                this.downloadMultipleForm.style.display = 'inline-block';
                this.multipleFileNamesInput.value = JSON.stringify(selectedFiles);
                this.multipleDownloadNamesInput.value = JSON.stringify(selectedFiles);

                this.deleteMultipleBtn.innerHTML = `
                <span class="btn-icon">🗑️</span>
                Delete Selected (${selectedFiles.length})
            `;
                this.downloadMultipleBtn.innerHTML = `
                <span class="btn-icon">⬇️</span>
                Download Selected (${selectedFiles.length})
            `;
            } else {
                this.deleteMultipleForm.style.display = 'none';
                this.downloadMultipleForm.style.display = 'none';
            }

            // refresh select all
            if (this.selectAllCheckbox) {
                this.selectAllCheckbox.checked = selectedFiles.length === this.fileCheckboxes.length && this.fileCheckboxes.length > 0;
                this.selectAllCheckbox.indeterminate = selectedFiles.length > 0 && selectedFiles.length < this.fileCheckboxes.length;
            }
        }

        handleMultipleDelete(e) {
            if (!confirm('Are you sure you want to delete selected files?')) {
                e.preventDefault();
                return false;
            }
            return true;
        }

        handleMultipleDownload(e) {
            console.log('Downloading selected files...');
            return true;
        }
    }

    /* =======================
     * FILE PREVIEW
     * ======================= */
    class FilePreviewManager {
        constructor(fileInput, previewContainer) {
            this.fileInput = fileInput;
            this.previewContainer = previewContainer;
            this.img = previewContainer.querySelector('img');
            this.info = previewContainer.querySelector('.file-info');

            this.init();
        }

        init() {
            this.fileInput.addEventListener('change', () => this.updatePreview());
        }

        updatePreview() {
            const file = this.fileInput.files[0];

            if (!file) {
                this.previewContainer.style.display = 'none';
                return;
            }

            this.previewContainer.style.display = 'block';
            this.info.innerHTML = `
                <div><strong>Name:</strong> ${file.name}</div>
                <div><strong>Size:</strong> ${formatBytes(file.size)}</div>
                <div><strong>Type:</strong> ${file.type || 'Unknown'}</div>
            `;

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.img.src = e.target.result;
                    this.img.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                this.img.style.display = 'none';
            }
        }

        reset() {
            this.previewContainer.style.display = 'none';
            this.img.src = '';
            this.img.style.display = 'none';
            this.info.innerHTML = '';
        }
    }

    /* =======================
     * Load init
     * ======================= */
    document.addEventListener('DOMContentLoaded', () => {
        const uploadModal = document.getElementById('upload-file-modal-folder');
        if (uploadModal) {
            uploadModal.style.display = 'none';

            new FileUploader({
                formSelector: '#upload-form-folder',
                fileInputSelector: '#file-input-folder',
                progressFillSelector: '#upload-file-modal-folder .progress-fill',
                progressTextSelector: '#upload-file-modal-folder .progress-text',
                uploadProgressSelector: '#upload-file-modal-folder .upload-progress',
                submitBtnSelector: '#upload-file-modal-folder #submit-upload-folder',
                cancelBtnSelector: '#upload-file-modal-folder #cancel-upload-file-folder',
                onSuccess: () => {
                    showNotification('File uploaded successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            });

            const fileInput = document.getElementById('file-input-folder');
            const previewContainer = uploadModal.querySelector('.file-preview');
            if (fileInput && previewContainer) {
                new FilePreviewManager(fileInput, previewContainer);
            }
        }

        if (document.querySelector('.file-select-checkbox')) {
            new FileSelectionManager();
        }

        const uploadBtn = document.getElementById('upload-file-folder');
        const uploadFirstBtn = document.getElementById('upload-first-file');
        const cancelUploadBtn = document.getElementById('cancel-upload-file-folder');

        const openUploadModal = () => modalManager.open(uploadModal);
        const closeUploadModal = () => modalManager.close();

        if (uploadBtn) uploadBtn.addEventListener('click', openUploadModal);
        if (uploadFirstBtn) uploadFirstBtn.addEventListener('click', openUploadModal);
        if (cancelUploadBtn) cancelUploadBtn.addEventListener('click', closeUploadModal);

        if (uploadModal) {
            uploadModal.addEventListener('click', e => {
                if (e.target === uploadModal) closeUploadModal();
            });
        }
    });

})();