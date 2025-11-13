'use strict';

(function (window, document) {
    const defaultOptions = {
        title: 'Confirmation',
        message: 'Êtes-vous sûr ?',
        confirmText: 'Confirmer',
        cancelText: 'Annuler',
        theme: 'default'
    };

    function createDialog(options) {
        const wrapper = document.createElement('div');
        wrapper.className = `aiolia-dialog-backdrop aiolia-dialog-theme-${options.theme}`;
        wrapper.innerHTML = `
            <div class="aiolia-dialog" role="dialog" aria-modal="true" aria-labelledby="aiolia-dialog-title">
                <div class="aiolia-dialog-header">
                    <h3 class="aiolia-dialog-title" id="aiolia-dialog-title">${options.title}</h3>
                    <button type="button" class="aiolia-dialog-close" aria-label="Fermer">×</button>
                </div>
                <div class="aiolia-dialog-body">${options.message}</div>
                <div class="aiolia-dialog-footer">
                    <button type="button" class="aiolia-dialog-btn aiolia-dialog-btn-cancel">${options.cancelText}</button>
                    <button type="button" class="aiolia-dialog-btn aiolia-dialog-btn-confirm">${options.confirmText}</button>
                </div>
            </div>
        `;
        return wrapper;
    }

    function animate(dialog) {
        requestAnimationFrame(() => {
            dialog.classList.add('aiolia-dialog-open');
        });
    }

    function close(dialog) {
        dialog.classList.remove('aiolia-dialog-open');
        setTimeout(() => dialog.remove(), 200);
    }

    function confirm(options = {}) {
        return new Promise((resolve, reject) => {
            const config = { ...defaultOptions, ...options };
            const dialog = createDialog(config);
            document.body.appendChild(dialog);

            animate(dialog);

            const confirmBtn = dialog.querySelector('.aiolia-dialog-btn-confirm');
            const cancelBtn = dialog.querySelector('.aiolia-dialog-btn-cancel');
            const closeBtn = dialog.querySelector('.aiolia-dialog-close');

            const handleConfirm = () => {
                close(dialog);
                resolve(true);
            };

            const handleCancel = () => {
                close(dialog);
                resolve(false);
            };

            confirmBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);
            closeBtn.addEventListener('click', handleCancel);

            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    handleCancel();
                }
            });

            window.addEventListener('keydown', function escHandler(event) {
                if (event.key === 'Escape') {
                    window.removeEventListener('keydown', escHandler);
                    handleCancel();
                }
            }, { once: true });
        });
    }

    window.aioliaConfirm = confirm;
})(window, document);

