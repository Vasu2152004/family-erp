/**
 * Fallback JavaScript - Loads when Vite assets are not available
 * Handles password toggles, sidebar functionality, and basic interactions
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initPasswordToggles();
        initSidebar();
        initFormFeedback();
    }

    /**
     * Initialize password visibility toggles
     */
    function initPasswordToggles() {
        const toggleButtons = document.querySelectorAll('[id^="toggle-password"]');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const buttonId = this.id;
                let passwordField;
                let eyeIcon;
                let eyeOffIcon;
                
                if (buttonId === 'toggle-password') {
                    passwordField = document.getElementById('password');
                    eyeIcon = document.getElementById('eye-icon');
                    eyeOffIcon = document.getElementById('eye-off-icon');
                } else if (buttonId === 'toggle-password-confirmation') {
                    passwordField = document.getElementById('password_confirmation');
                    eyeIcon = document.getElementById('eye-icon-confirmation');
                    eyeOffIcon = document.getElementById('eye-off-icon-confirmation');
                }
                
                if (!passwordField) return;
                
                const isPassword = passwordField.type === 'password';
                passwordField.type = isPassword ? 'text' : 'password';
                
                if (eyeIcon && eyeOffIcon) {
                    if (isPassword) {
                        eyeIcon.classList.add('hidden');
                        eyeOffIcon.classList.remove('hidden');
                    } else {
                        eyeIcon.classList.remove('hidden');
                        eyeOffIcon.classList.add('hidden');
                    }
                }
            });
        });
    }

    /**
     * Initialize sidebar toggle functionality
     */
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarClose = document.getElementById('sidebar-close');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (!sidebar) return;

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('hidden');
            }
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            if (sidebarOverlay) {
                sidebarOverlay.classList.add('hidden');
            }
            document.body.style.overflow = '';
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', openSidebar);
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                closeSidebar();
            }
        });
    }

    /**
     * Initialize form feedback and validation
     */
    function initFormFeedback() {
        const forms = document.querySelectorAll('form[id$="-form"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitButton = form.querySelector('button[type="submit"]');
                
                if (submitButton) {
                    // Disable button to prevent double submission
                    submitButton.disabled = true;
                    const originalText = submitButton.textContent || submitButton.innerText;
                    
                    if (originalText.includes('Sign')) {
                        submitButton.textContent = 'Signing in...';
                    } else if (originalText.includes('Create')) {
                        submitButton.textContent = 'Creating...';
                    } else if (originalText.includes('Reset')) {
                        submitButton.textContent = 'Resetting...';
                    } else {
                        submitButton.textContent = 'Processing...';
                    }
                }
            });
        });
        
        // Clear validation errors on input
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const errorMessage = this.parentElement.querySelector('.text-\\[var\\(--color-error\\)\\]');
                if (errorMessage && this.value.trim() !== '') {
                    this.classList.remove('border-[var(--color-error)]');
                    this.classList.add('border-[var(--color-border-primary)]');
                }
            });
        });
    }
})();













