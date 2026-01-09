(function () {
    'use strict';

    const {modalManager, FileUploader, showNotification} = window.sharedUtils;

    /* =======================
     * ELEMENTS
     * ======================= */
    const folderModal = document.getElementById('create-folder-modal');
    const uploadModal = document.getElementById('upload-file-modal');

    const folderOpenBtn = document.getElementById('create-folder-btn');
    const uploadOpenBtn = document.getElementById('upload-file-btn');

    const folderCloseBtn = document.getElementById('cancel-create-folder');
    const uploadCloseBtn = document.getElementById('cancel-upload-file');

    document.addEventListener('DOMContentLoaded', () => {
        if (folderModal) folderModal.style.display = 'none';
        if (uploadModal) uploadModal.style.display = 'none';

        if (uploadModal) {
            new FileUploader({
                formSelector: '#upload-file-modal form',
                fileInputSelector: '#upload-file-modal input[type="file"]',
                folderSelectSelector: '#upload-file-modal select[name="folder"]',
                progressFillSelector: '#upload-file-modal .progress-fill',
                progressTextSelector: '#upload-file-modal .progress-text',
                uploadProgressSelector: '#upload-file-modal .upload-progress',
                submitBtnSelector: '#upload-file-modal #submit-upload',
                cancelBtnSelector: '#upload-file-modal #cancel-upload-file'
            });
        }

        if (folderOpenBtn) {
            folderOpenBtn.addEventListener('click', () => modalManager.open(folderModal));
        }

        if (uploadOpenBtn) {
            uploadOpenBtn.addEventListener('click', () => modalManager.open(uploadModal));
        }

        if (folderCloseBtn) {
            folderCloseBtn.addEventListener('click', () => modalManager.close());
        }

        if (uploadCloseBtn) {
            uploadCloseBtn.addEventListener('click', () => modalManager.close());
        }

        [folderModal, uploadModal].forEach(modal => {
            if (modal) {
                modal.addEventListener('click', e => {
                    if (e.target === modal) modalManager.close();
                });
            }
        });
    });

})();