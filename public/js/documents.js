const setupDocumentDownloads = () => {
    const buttons = Array.from(document.querySelectorAll('.document-download-btn'));
    if (!buttons.length) return;

    const modal = document.getElementById('password-modal');
    const passwordInput = document.getElementById('document-password');
    const passwordError = document.getElementById('password-error');
    const passwordSubmit = document.getElementById('password-submit');
    const passwordCancel = document.getElementById('password-cancel');
    const downloadForm = document.getElementById('document-download-form');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let activeButton = null;

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        passwordInput.value = '';
        passwordError.classList.add('hidden');
        activeButton = null;
    };

    const openModal = (button) => {
        activeButton = button;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => passwordInput?.focus(), 50);
    };

    const submitDownload = (url) => {
        // Use window.location for GET requests to trigger download
        window.location.href = url;
    };

    const verifyAndDownload = async () => {
        if (!activeButton) return;
        const verifyUrl = activeButton.dataset.verifyUrl;
        const downloadUrl = activeButton.dataset.downloadUrl;
        const password = passwordInput.value;

        if (!verifyUrl || !downloadUrl) return;

        passwordError.classList.add('hidden');

        try {
            const response = await fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ password }),
            });

            if (!response.ok) {
                passwordError.classList.remove('hidden');
                return;
            }

            closeModal();
            submitDownload(downloadUrl);
        } catch (error) {
            passwordError.classList.remove('hidden');
        }
    };

    buttons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const requiresPassword = button.dataset.requiresPassword === '1';

            if (!requiresPassword) {
                submitDownload(button.dataset.downloadUrl);
                return;
            }

            openModal(button);
        });
    });

    passwordSubmit?.addEventListener('click', verifyAndDownload);
    passwordCancel?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    passwordInput?.addEventListener('keyup', (event) => {
        if (event.key === 'Enter') {
            verifyAndDownload();
        }
    });
};

document.addEventListener('DOMContentLoaded', setupDocumentDownloads);

