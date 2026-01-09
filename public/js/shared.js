// /js/shared.js
(function () {
    'use strict';

    /* =======================
     * CONFIG
     * ======================= */
    const SMALL_FILE_LIMIT = 256 * 1024 * 1024; // 256MB
    const TUS_ENDPOINT = '/storage/tus';

    /* =======================
     * STATE
     * ======================= */
    let currentUploadType = '';

    /* =======================
     * UTILS
     * ======================= */
    function formatBytes(bytes) {
        if (!bytes) return '0 Bytes';
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
    }

    function getErrorMessage(code) {
        const errors = {
            no_file: 'No file selected',
            upload_failed: 'Upload failed',
            storage_limit: 'Storage limit exceeded',
            invalid_folder: 'Invalid folder selected'
        };
        return errors[code] || 'Unknown error';
    }

    function getSuccessMessage(code) {
        const success = {
            file_uploaded: 'File uploaded successfully',
            folder_created: 'Folder created successfully',
            files_deleted: 'Files deleted successfully'
        };
        return success[code] || 'Success';
    }

    /* =======================
     * NOTIFICATIONS
     * ======================= */
    window.showNotification = function (message, type = 'info') {
        const body = document.body;
        const notification = document.createElement('div');

        const oldNotifications = document.querySelectorAll('.global-notification');
        oldNotifications.forEach(n => n.remove());

        notification.className = 'global-notification';
        notification.textContent = message;

        const bgColor = type === 'error' ? '#e53e3e' :
            type === 'success' ? '#38a169' :
                type === 'warning' ? '#d69e2e' : '#3182ce';

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 16px;
            background: ${bgColor};
            color: #fff;
            border-radius: 8px;
            z-index: 10000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease-out;
        `;

        body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 3000);

        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
    };

    /* =======================
     * MODAL MANAGEMENT
     * ======================= */
    window.modalManager = {
        currentModal: null,

        open(modal) {
            this.closeAll();
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            this.currentModal = modal;

            const escHandler = (e) => {
                if (e.key === 'Escape') this.close();
            };
            modal._escHandler = escHandler;
            document.addEventListener('keydown', escHandler);
        },

        close() {
            if (this.currentModal) {
                document.removeEventListener('keydown', this.currentModal._escHandler);
                this.currentModal.style.display = 'none';
                document.body.style.overflow = '';
                this.currentModal = null;
            }
        },

        closeAll() {
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.style.display = 'none';
                if (modal._escHandler) {
                    document.removeEventListener('keydown', modal._escHandler);
                }
            });
            document.body.style.overflow = '';
            this.currentModal = null;
        }
    };

    /* =======================
     * FILE UPLOAD
     * ======================= */
    class FileUploader {
        constructor(options = {}) {
            this.options = {
                formSelector: '',
                fileInputSelector: '',
                folderSelectSelector: null,
                progressFillSelector: '.progress-fill',
                progressTextSelector: '.progress-text',
                uploadProgressSelector: '.upload-progress',
                submitBtnSelector: '',
                cancelBtnSelector: '',
                onSuccess: null,
                onError: null,
                ...options
            };

            this.form = document.querySelector(this.options.formSelector);
            this.fileInput = document.querySelector(this.options.fileInputSelector);
            this.folderSelect = this.options.folderSelectSelector ?
                document.querySelector(this.options.folderSelectSelector) : null;
            this.progressFill = document.querySelector(this.options.progressFillSelector);
            this.progressText = document.querySelector(this.options.progressTextSelector);
            this.uploadProgress = document.querySelector(this.options.uploadProgressSelector);
            this.submitBtn = document.querySelector(this.options.submitBtnSelector);
            this.cancelBtn = document.querySelector(this.options.cancelBtnSelector);

            this.bindEvents();
        }

        bindEvents() {
            if (this.form) {
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            }

            if (this.fileInput) {
                this.fileInput.addEventListener('change', () => this.handleFileSelect());
            }

            if (this.cancelBtn) {
                this.cancelBtn.addEventListener('click', () => this.reset());
            }
        }

        handleFileSelect() {
            const file = this.fileInput.files[0];
            if (!file) return;

            console.log('File selected:', file.name, formatBytes(file.size));
        }

        async handleSubmit(e) {
            e.preventDefault();

            const file = this.fileInput.files[0];
            const folder = this.folderSelect ? this.folderSelect.value : this.form.querySelector('input[name="folder"]')?.value;

            if (!file) {
                showNotification('Please select a file', 'error');
                return;
            }

            if (this.folderSelect && !folder) {
                showNotification('Please select a folder', 'error');
                return;
            }

            this.setUploadingState(true);

            if (file.size <= SMALL_FILE_LIMIT) {
                await this.uploadSmallFile(file, folder);
            } else {
                this.uploadLargeFile(file, folder);
            }
        }

        async uploadSmallFile(file, folder) {
            try {
                this.updateProgress(0, 'Starting upload...');

                const formData = new FormData();
                formData.append('file', file);
                if (folder) formData.append('folder', folder);
                formData.append('_csrf', this.form.querySelector('input[name="_csrf"]')?.value || '');

                let progress = 0;
                const progressInterval = setInterval(() => {
                    if (progress < 90) {
                        progress += 10;
                        this.updateProgress(progress);
                    }
                }, 300);

                const response = await fetch('/storage/files', {
                    method: 'POST',
                    body: formData
                });

                clearInterval(progressInterval);

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Upload failed');
                }

                this.updateProgress(100, 'Upload complete!');
                this.onUploadSuccess();

            } catch (error) {
                this.onUploadError(error.message);
            }
        }

        uploadLargeFile(file, folder) {
            this.updateProgress(0, 'Starting large file upload...');

            const upload = new tus.Upload(file, {
                endpoint: TUS_ENDPOINT,
                chunkSize: 5 * 1024 * 1024,
                retryDelays: [0, 1000, 3000, 5000],
                metadata: {
                    folder: folder || '',
                    filename: file.name
                },
                withCredentials: true,

                onProgress: (uploaded, total) => {
                    const percent = Math.round((uploaded / total) * 100);
                    this.updateProgress(percent, `${percent}%`);
                },

                onSuccess: () => {
                    this.updateProgress(100, 'Upload complete!');
                    setTimeout(() => this.onUploadSuccess(), 500);
                },

                onError: (error) => {
                    this.onUploadError(error.toString());
                }
            });

            upload.start();
        }

        updateProgress(percent, text = '') {
            if (this.progressFill) {
                this.progressFill.style.width = percent + '%';
            }
            if (this.progressText) {
                this.progressText.textContent = text || percent + '%';
            }
        }

        onUploadSuccess() {
            showNotification('File uploaded successfully', 'success');
            this.setUploadingState(false);
            modalManager.close();
            this.reset();

            if (this.options.onSuccess) {
                this.options.onSuccess();
            } else {
                setTimeout(() => location.reload(), 1000);
            }
        }

        onUploadError(error) {
            showNotification(error, 'error');
            this.setUploadingState(false);
            this.reset();

            if (this.options.onError) {
                this.options.onError(error);
            }
        }

        setUploadingState(uploading) {
            if (this.submitBtn) this.submitBtn.disabled = uploading;
            if (this.cancelBtn) this.cancelBtn.disabled = uploading;
            if (this.uploadProgress) {
                this.uploadProgress.style.display = uploading ? 'block' : 'none';
            }
        }

        reset() {
            if (this.form) this.form.reset();
            this.updateProgress(0);
            this.setUploadingState(false);
        }
    }

    /* =======================
     * INIT URL PARAMS
     * ======================= */
    function initUrlParams() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('error')) {
            showNotification(getErrorMessage(params.get('error')), 'error');
        }
        if (params.get('success')) {
            showNotification(getSuccessMessage(params.get('success')), 'success');
        }
    }

    /* =======================
     * EXPORTS
     * ======================= */
    window.sharedUtils = {
        formatBytes,
        getErrorMessage,
        getSuccessMessage,
        showNotification,
        modalManager,
        FileUploader,
        initUrlParams,
        SMALL_FILE_LIMIT,
        TUS_ENDPOINT
    };

    document.addEventListener('DOMContentLoaded', initUrlParams);

})();