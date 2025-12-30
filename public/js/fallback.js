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
        initFormValidation();
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
                    // Store original text before changing it
                    if (!submitButton.getAttribute('data-original-text')) {
                        submitButton.setAttribute('data-original-text', submitButton.textContent.trim());
                    }
                    
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

    /**
     * Initialize form validation with real-time feedback
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form.needs-validation, form[data-validate]');
        const isAuthPage = document.body && document.body.classList.contains('min-h-screen');
        
        // Prevent links from triggering validation on auth pages
        if (isAuthPage && !window._authLinkHandlerAdded) {
            window._authLinkHandlerAdded = true;
            
            // Handle link clicks - set flag before blur fires
            document.addEventListener('mousedown', function(e) {
                const link = e.target.closest('a[useJsNav="true"], a[href*="login"], a[href*="register"]');
                if (link && (link.closest('[data-auth-form]') || link.closest('.max-w-md'))) {
                    // Set flag immediately on mousedown (before blur)
                    window._linkClicked = true;
                    // Clear any validation messages
                    document.querySelectorAll('form').forEach(function(form) {
                        const fields = form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), textarea, select');
                        fields.forEach(function(field) {
                            const fieldName = field.name || field.id;
                            const errorContainer = field.parentElement.querySelector('[data-field-error="' + fieldName + '"]');
                            if (errorContainer) errorContainer.remove();
                            const successContainer = field.parentElement.querySelector('[data-field-success="' + fieldName + '"]');
                            if (successContainer) successContainer.remove();
                            field.classList.remove('border-[var(--color-error)]', 'focus:ring-[var(--color-error)]', 'focus:border-[var(--color-error)]', 'border-green-500', 'focus:ring-green-500', 'focus:border-green-500');
                        });
                    });
                }
            }, true); // Capture phase - runs before blur
        }
        
        forms.forEach(form => {
            form.setAttribute('novalidate', 'novalidate');
            const fields = form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), textarea, select');
            
            fields.forEach(field => {
                // Real-time validation on blur - but skip if link is being clicked
                field.addEventListener('blur', function(e) {
                    // On auth pages, check if a link was just clicked
                    if (isAuthPage && window._linkClicked) {
                        window._linkClicked = false;
                        return; // Skip validation
                    }
                    validateField(this);
                });
                
                // Clear error on input
                field.addEventListener('input', function() {
                    clearFieldError(this);
                });
                
                // Prevent default browser validation
                field.addEventListener('invalid', function(e) {
                    e.preventDefault();
                    validateField(this);
                });
            });
            
            // Prevent links from triggering form validation
            const linkClickHandler = function(e) {
                const link = e.target.closest('a');
                if (link) {
                    const isRelatedToForm = form.contains(link) || 
                                           link.closest('[data-auth-form]') ||
                                           link.closest('.max-w-md');
                    if (isRelatedToForm) {
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        return true;
                    }
                }
            };
            
            // Add listener in capture phase to catch before form validation
            document.addEventListener('click', linkClickHandler, true);
            
            // Form submission validation - only on actual form submission
            form.addEventListener('submit', function(e) {
                const submitter = e.submitter;
                if (submitter && (submitter.tagName === 'A' || submitter.closest('a'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                
                let isValid = true;
                fields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Focus on first invalid field
                    const firstInvalid = form.querySelector(':invalid, .border-\\[var\\(--color-error\\)\\]');
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    }

    function validateField(field) {
        const isValid = field.checkValidity();
        clearFieldError(field);
        
        if (!isValid) {
            showFieldError(field, field.validationMessage);
            return false;
        }
        return true;
    }

    function showFieldError(field, message) {
        const fieldName = field.name || field.id;
        field.classList.add('border-[var(--color-error)]', 'focus:ring-[var(--color-error)]', 'focus:border-[var(--color-error)]');
        field.classList.remove('border-[var(--color-border-primary)]');
        
        let errorContainer = field.parentElement.querySelector('[data-field-error="' + fieldName + '"]');
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.setAttribute('data-field-error', fieldName);
            errorContainer.className = 'mt-2 flex items-start gap-2 animate-fade-in';
            errorContainer.setAttribute('role', 'alert');
            const parent = field.closest('.form-field, div') || field.parentElement;
            if (field.nextSibling) {
                parent.insertBefore(errorContainer, field.nextSibling);
            } else {
                parent.appendChild(errorContainer);
            }
        }
        
        errorContainer.innerHTML = '<svg class="w-5 h-5 text-[var(--color-error)] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg><p class="text-sm font-medium text-[var(--color-error)] leading-relaxed">' + escapeHtml(message) + '</p>';
        
        field.classList.add('animate-shake');
        setTimeout(function() {
            field.classList.remove('animate-shake');
        }, 500);
    }

    function clearFieldError(field) {
        const fieldName = field.name || field.id;
        field.classList.remove('border-[var(--color-error)]', 'focus:ring-[var(--color-error)]', 'focus:border-[var(--color-error)]');
        const errorContainer = field.parentElement.querySelector('[data-field-error="' + fieldName + '"]');
        if (errorContainer) {
            errorContainer.remove();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();




















