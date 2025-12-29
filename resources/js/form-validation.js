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
        
        // Add event listeners
        this.fields.forEach(field => {
            // Real-time validation on blur
            field.addEventListener('blur', () => this.validateField(field));
            
            // Clear error on input (for better UX)
            field.addEventListener('input', () => this.clearFieldError(field));
            
            // Prevent form submission if invalid
            field.addEventListener('invalid', (e) => {
                e.preventDefault();
                this.validateField(field);
            });
        });

        // Form submission validation
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Focus on first invalid field
                const firstInvalid = this.form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
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
            // Optional: Show success state for certain fields
            if (field.type === 'email' && field.value) {
                this.showFieldSuccess(field, 'Email format is valid');
            }
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

