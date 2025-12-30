/**
 * Custom Form Validation System
 * Provides real-time validation feedback with Tailwind CSS styling
 */

class FormValidation {
    constructor(formSelector) {
        this.form = typeof formSelector === 'string' 
            ? document.querySelector(formSelector) 
            : formSelector;
        
        if (!this.form) {
            console.warn('Form not found:', formSelector);
            return;
        }

        this.init();
    }

    init() {
        // Add validation classes to form
        this.form.classList.add('needs-validation');
        this.form.setAttribute('novalidate', 'novalidate');

        // Get all inputs, textareas, and selects
        this.fields = this.form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), textarea, select');
        
        // Check if we're on an auth page
        const isAuthPage = document.body && document.body.classList.contains('min-h-screen');
        
        // Add event listeners
        this.fields.forEach(field => {
            // Real-time validation on blur - but skip if link is being clicked
            field.addEventListener('blur', (e) => {
                // On auth pages, check if a link was just clicked
                if (isAuthPage && window._linkClicked) {
                    window._linkClicked = false;
                    return; // Skip validation
                }
                this.validateField(field);
            });
            
            // Clear error on input (for better UX)
            field.addEventListener('input', () => this.clearFieldError(field));
            
            // Prevent form submission if invalid
            field.addEventListener('invalid', (e) => {
                e.preventDefault();
                this.validateField(field);
            });
        });

        // Prevent links from triggering form validation on auth pages
        if (isAuthPage && !window._authLinkHandlerAdded) {
            window._authLinkHandlerAdded = true;
            
            // Handle link clicks - set flag before blur fires
            document.addEventListener('mousedown', (e) => {
                const link = e.target.closest('a[useJsNav="true"], a[href*="login"], a[href*="register"]');
                if (link && (link.closest('[data-auth-form]') || link.closest('.max-w-md'))) {
                    // Set flag immediately on mousedown (before blur)
                    window._linkClicked = true;
                    // Clear any validation messages
                    document.querySelectorAll('form').forEach(form => {
                        const fields = form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), textarea, select');
                        fields.forEach(field => {
                            const fieldName = field.name || field.id;
                            const errorContainer = field.parentElement.querySelector(`[data-field-error="${fieldName}"]`);
                            if (errorContainer) errorContainer.remove();
                            const successContainer = field.parentElement.querySelector(`[data-field-success="${fieldName}"]`);
                            if (successContainer) successContainer.remove();
                            field.classList.remove('border-[var(--color-error)]', 'focus:ring-[var(--color-error)]', 'focus:border-[var(--color-error)]', 'border-green-500', 'focus:ring-green-500', 'focus:border-green-500');
                        });
                    });
                }
            }, true); // Capture phase - runs before blur
        }

        // Form submission validation - only validate on actual form button submission
        this.form.addEventListener('submit', (e) => {
            // Check if submit was triggered by a link (shouldn't happen, but safety check)
            const submitter = e.submitter;
            if (submitter && (submitter.tagName === 'A' || submitter.closest('a'))) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            
            // Get submit button before validation
            const submitButton = this.form.querySelector('button[type="submit"]');
            
            // Only validate on actual form button submission
            if (!this.validateForm()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Mark form as NOT submitting (validation failed)
                this.form.removeAttribute('data-submitting');
                
                // Cancel any pending button text change from auth.js
                if (submitButton) {
                    const timeoutId = submitButton.getAttribute('data-timeout-id');
                    if (timeoutId) {
                        clearTimeout(parseInt(timeoutId));
                        submitButton.removeAttribute('data-timeout-id');
                    }
                    
                    // Ensure button is enabled and restore text
                    submitButton.disabled = false;
                    const originalText = submitButton.getAttribute('data-original-text');
                    if (originalText) {
                        submitButton.textContent = originalText;
                    } else {
                        // Try to restore based on common patterns
                        const currentText = submitButton.textContent;
                        if (currentText.includes('Signing')) {
                            submitButton.textContent = 'Sign In';
                        } else if (currentText.includes('Creating')) {
                            submitButton.textContent = 'Create Account';
                        } else if (currentText.includes('Resetting')) {
                            submitButton.textContent = 'Reset Password';
                        } else if (currentText.includes('Processing')) {
                            submitButton.textContent = 'Submit';
                        }
                    }
                }
                
                // Focus on first invalid field
                const firstInvalid = this.form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
        }, true); // Use capture phase to run BEFORE auth.js
    }

    validateField(field) {
        const fieldName = field.name || field.id;
        const isValid = field.checkValidity();
        
        // Remove previous validation states
        this.clearFieldError(field);
        this.clearFieldSuccess(field);

        if (!isValid) {
            this.showFieldError(field, field.validationMessage);
            return false;
        } else {
            // Success validation - no success messages shown
            return true;
        }
    }

    validateForm() {
        let isValid = true;
        
        this.fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    showFieldError(field, message) {
        const fieldName = field.name || field.id;
        
        // Add error classes to field
        field.classList.add('border-[var(--color-error)]', 'focus:ring-[var(--color-error)]', 'focus:border-[var(--color-error)]');
        field.classList.remove('border-[var(--color-border-primary)]', 'border-green-500');

        // Create or update error message
        let errorContainer = field.parentElement.querySelector(`[data-field-error="${fieldName}"]`);
        
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.setAttribute('data-field-error', fieldName);
            errorContainer.className = 'mt-2 flex items-start gap-2 animate-fade-in';
            errorContainer.setAttribute('role', 'alert');
            errorContainer.setAttribute('aria-live', 'polite');
            
            // Insert after field or in parent container
            const parent = field.closest('.form-field, div') || field.parentElement;
            if (field.nextSibling) {
                parent.insertBefore(errorContainer, field.nextSibling);
            } else {
                parent.appendChild(errorContainer);
            }
        }

        errorContainer.innerHTML = `
            <svg class="w-5 h-5 text-[var(--color-error)] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-[var(--color-error)] leading-relaxed">
                ${this.escapeHtml(message)}
            </p>
        `;

        // Add shake animation
        field.classList.add('animate-shake');
        setTimeout(() => {
            field.classList.remove('animate-shake');
        }, 500);
    }

    showFieldSuccess(field, message) {
        const fieldName = field.name || field.id;
        
        // Add success classes to field
        field.classList.add('border-green-500', 'focus:ring-green-500', 'focus:border-green-500');
        field.classList.remove('border-[var(--color-error)]');

        // Create or update success message
        let successContainer = field.parentElement.querySelector(`[data-field-success="${fieldName}"]`);
        
        if (!successContainer) {
            successContainer = document.createElement('div');
            successContainer.setAttribute('data-field-success', fieldName);
            successContainer.className = 'mt-2 flex items-start gap-2 animate-fade-in';
            successContainer.setAttribute('role', 'status');
            successContainer.setAttribute('aria-live', 'polite');
            
            const parent = field.closest('.form-field, div') || field.parentElement;
            if (field.nextSibling) {
                parent.insertBefore(successContainer, field.nextSibling);
            } else {
                parent.appendChild(successContainer);
            }
        }

        successContainer.innerHTML = `
            <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-green-600 leading-relaxed">
                ${this.escapeHtml(message)}
            </p>
        `;
    }

    clearFieldError(field) {
        const fieldName = field.name || field.id;
        
        // Remove error classes
        field.classList.remove('border-[var(--color-error)]', 'focus:ring-[var(--color-error)]', 'focus:border-[var(--color-error)]');
        
        // Remove error message
        const errorContainer = field.parentElement.querySelector(`[data-field-error="${fieldName}"]`);
        if (errorContainer) {
            errorContainer.remove();
        }
    }

    clearFieldSuccess(field) {
        const fieldName = field.name || field.id;
        
        // Remove success classes
        field.classList.remove('border-green-500', 'focus:ring-green-500', 'focus:border-green-500');
        
        // Remove success message
        const successContainer = field.parentElement.querySelector(`[data-field-success="${fieldName}"]`);
        if (successContainer) {
            successContainer.remove();
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Static method to initialize all forms on page
    static initAll() {
        document.querySelectorAll('form.needs-validation, form[data-validate]').forEach(form => {
            new FormValidation(form);
        });
    }
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        FormValidation.initAll();
    });
} else {
    FormValidation.initAll();
}

// Export for use in other scripts
window.FormValidation = FormValidation;

