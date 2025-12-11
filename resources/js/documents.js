const setupDocumentDownloads = () => {
    console.log('Initializing document downloads...');
    const buttons = Array.from(document.querySelectorAll('.document-download-btn'));
    console.log('Found', buttons.length, 'download buttons');
    
    if (!buttons.length) {
        console.log('No download buttons found');
        return;
    }

    const modal = document.getElementById('password-modal');
    const passwordInput = document.getElementById('document-password');
    const passwordError = document.getElementById('password-error');
    const passwordSubmit = document.getElementById('password-submit');
    const passwordCancel = document.getElementById('password-cancel');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!modal) {
        console.error('Password modal not found');
        return;
    }
    if (!passwordInput) {
        console.error('Password input not found');
        return;
    }
    if (!passwordSubmit) {
        console.error('Password submit button not found');
        return;
    }
    
    console.log('All modal elements found, ready to handle downloads');

    let activeButton = null;

    const closeModal = () => {
        modal.style.display = 'none';
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (passwordInput) passwordInput.value = '';
        if (passwordError) passwordError.classList.add('hidden');
        activeButton = null;
    };

    const openModal = (button) => {
        activeButton = button;
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            if (passwordInput) {
                passwordInput.focus();
                passwordInput.value = '';
            }
        }, 100);
    };

    const submitDownload = (url) => {
        // Use window.location for GET requests to trigger download
        window.location.href = url;
    };

    const verifyAndDownload = async () => {
        if (!activeButton) return;
        
        const verifyUrl = activeButton.dataset.verifyUrl;
        const downloadUrl = activeButton.dataset.downloadUrl;
        const password = passwordInput?.value.trim();

        if (!verifyUrl || !downloadUrl) {
            console.error('Missing URLs');
            return;
        }

        if (!password) {
            if (passwordError) {
                passwordError.textContent = 'Please enter a password';
                passwordError.classList.remove('hidden');
            }
            return;
        }

        if (passwordError) passwordError.classList.add('hidden');

        try {
            const response = await fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ password: password }),
            });

            const data = await response.json();

            if (!response.ok) {
                if (passwordError) {
                    passwordError.textContent = data.message || 'Incorrect password. Please try again.';
                    passwordError.classList.remove('hidden');
                }
                if (passwordInput) {
                    passwordInput.value = '';
                    passwordInput.focus();
                }
                return;
            }

            closeModal();
            submitDownload(downloadUrl);
        } catch (error) {
            console.error('Error:', error);
            if (passwordError) {
                passwordError.textContent = 'An error occurred. Please try again.';
                passwordError.classList.remove('hidden');
            }
        }
    };

    buttons.forEach((button) => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const requiresPassword = this.dataset.requiresPassword === '1';
            console.log('Button clicked. requiresPassword:', requiresPassword, 'dataset:', {
                requiresPassword: this.dataset.requiresPassword,
                isProtected: this.dataset.isProtected,
                hasPassword: this.dataset.hasPassword
            });
            
            if (requiresPassword) {
                console.log('Password required, opening modal');
                openModal(this);
            } else {
                console.log('No password required, downloading directly');
                submitDownload(this.dataset.downloadUrl);
            }
        });
    });
    
    // Handle test password buttons (for admins to test password functionality)
    const testButtons = document.querySelectorAll('.test-password-btn');
    testButtons.forEach((button) => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('Test password clicked');
            openModal(this);
        });
    });

    if (passwordSubmit) {
        passwordSubmit.addEventListener('click', function(e) {
            e.preventDefault();
            verifyAndDownload();
        });
    }

    if (passwordCancel) {
        passwordCancel.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                verifyAndDownload();
            }
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupDocumentDownloads);
} else {
    setupDocumentDownloads();
}

