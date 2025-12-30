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
        initCustomAlerts();
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

    /**
     * Custom Alerts and Confirmations System
     * Replaces browser default alert(), confirm(), and prompt() with custom UI components
     */

    /**
     * Show a custom alert notification
     */
    function showAlert(message, type, duration) {
        type = type || 'info';
        duration = duration || 5000;

        // Create alert container if it doesn't exist
        let alertContainer = document.getElementById('custom-alerts-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'custom-alerts-container';
            alertContainer.className = 'fixed top-4 right-4 z-50 space-y-2 max-w-md w-full';
            document.body.appendChild(alertContainer);
        }

        // Create alert element
        const alertId = 'alert-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        const alertElement = document.createElement('div');
        alertElement.id = alertId;
        alertElement.setAttribute('role', 'alert');
        alertElement.setAttribute('aria-live', 'polite');

        // Set alert type classes
        const typeClasses = {
            success: 'bg-green-50 border-green-200 text-green-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            info: 'bg-blue-50 border-blue-200 text-blue-800',
        };

        const classes = 'p-4 rounded-lg border animate-fade-in ' + (typeClasses[type] || typeClasses.info);
        alertElement.className = classes;

        // Create alert content
        alertElement.innerHTML = '<div class="flex items-start"><div class="flex-1">' + escapeHtml(message) + '</div><button type="button" class="ml-4 text-current opacity-70 hover:opacity-100 transition-opacity" onclick="dismissAlert(\'' + alertId + '\')" aria-label="Close"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></button></div>';

        // Add to container
        alertContainer.appendChild(alertElement);

        // Auto-dismiss if duration is set
        if (duration > 0) {
            setTimeout(function() {
                dismissAlert(alertId);
            }, duration);
        }
    }

    /**
     * Dismiss an alert
     */
    function dismissAlert(alertId) {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            alertElement.style.opacity = '0';
            alertElement.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                alertElement.remove();
            }, 300);
        }
    }

    /**
     * Show a custom confirmation modal
     */
    function showConfirm(options) {
        options = options || {};
        const title = options.title || 'Confirm Action';
        const message = options.message || 'Are you sure you want to proceed?';
        const confirmText = options.confirmText || 'Confirm';
        const cancelText = options.cancelText || 'Cancel';
        const variant = options.variant || 'primary';

        // Create or get modal container
        let modalContainer = document.getElementById('confirm-modal-container');
        if (!modalContainer) {
            modalContainer = document.createElement('div');
            modalContainer.id = 'confirm-modal-container';
            document.body.appendChild(modalContainer);
        }

        // Generate unique modal ID
        const modalId = 'confirm-modal-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        // Create modal HTML
        const variantClass = variant === 'danger' 
            ? 'bg-red-600 hover:bg-red-700 text-white' 
            : 'bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white';

        const modalHTML = '<div id="' + modalId + '" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="' + modalId + '-title" aria-describedby="' + modalId + '-message"><div class="bg-[var(--color-bg-primary)] rounded-xl shadow-xl border border-[var(--color-border-primary)] max-w-md w-full p-6 animate-fade-in"><div class="flex items-center justify-between mb-4"><h3 id="' + modalId + '-title" class="text-xl font-bold text-[var(--color-text-primary)]">' + escapeHtml(title) + '</h3><button type="button" class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors" onclick="closeConfirmModal(\'' + modalId + '\')" aria-label="Close"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div><p id="' + modalId + '-message" class="text-[var(--color-text-secondary)] mb-6">' + escapeHtml(message) + '</p><div class="flex justify-end gap-3"><button type="button" id="' + modalId + '-cancel-btn" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors font-medium">' + escapeHtml(cancelText) + '</button><button type="button" id="' + modalId + '-confirm-btn" class="px-4 py-2 rounded-lg font-medium transition-colors ' + variantClass + '">' + escapeHtml(confirmText) + '</button></div></div></div>';

        // Insert modal
        modalContainer.innerHTML = modalHTML;
        const modal = document.getElementById(modalId);

        // Return promise
        return new Promise(function(resolve, reject) {
            const confirmBtn = document.getElementById(modalId + '-confirm-btn');
            const cancelBtn = document.getElementById(modalId + '-cancel-btn');

            const cleanup = function() {
                modal.style.transition = 'opacity 0.3s ease-out';
                modal.style.opacity = '0';
                setTimeout(function() {
                    modal.remove();
                }, 300);
            };

            const handleConfirm = function() {
                cleanup();
                resolve();
            };

            const handleCancel = function() {
                cleanup();
                reject(new Error('User cancelled'));
            };

            // Event listeners
            confirmBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);

            // Close on backdrop click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    handleCancel();
                }
            });

            // Close on ESC key
            const handleEsc = function(e) {
                if (e.key === 'Escape') {
                    handleCancel();
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);

            // Focus on confirm button
            setTimeout(function() {
                confirmBtn.focus();
            }, 100);
        });
    }

    /**
     * Close a confirmation modal
     */
    function closeConfirmModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.transition = 'opacity 0.3s ease-out';
            modal.style.opacity = '0';
            setTimeout(function() {
                modal.remove();
            }, 300);
        }
    }

    /**
     * Initialize form confirmation handlers
     */
    function initCustomAlerts() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            
            // Check if form has data-confirm attribute
            if (form.hasAttribute('data-confirm')) {
                e.preventDefault();
                e.stopPropagation();

                const message = form.getAttribute('data-confirm');
                const title = form.getAttribute('data-confirm-title') || 'Confirm Action';
                const variant = form.getAttribute('data-confirm-variant') || 'primary';

                showConfirm({
                    title: title,
                    message: message,
                    variant: variant,
                    confirmText: form.getAttribute('data-confirm-text') || 'Confirm',
                    cancelText: form.getAttribute('data-cancel-text') || 'Cancel'
                }).then(function() {
                    // User confirmed - submit the form
                    form.removeAttribute('data-confirm');
                    form.submit();
                }).catch(function() {
                    // User cancelled - do nothing
                });
            }
        }, true); // Use capture phase to catch before other handlers
    }

    // Make functions globally available
    window.showAlert = showAlert;
    window.showConfirm = showConfirm;
    window.dismissAlert = dismissAlert;
    window.closeConfirmModal = closeConfirmModal;
})();




















