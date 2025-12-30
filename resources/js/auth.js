/**
 * Authentication Form Handling
 * Handles password visibility toggles and form interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle functionality
    initPasswordToggles();
    
    // Form submission feedback
    initFormFeedback();
});

/**
 * Initialize password visibility toggles
 */
function initPasswordToggles() {
    // Find all password toggle buttons
    const toggleButtons = document.querySelectorAll('[id^="toggle-password"]');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const buttonId = this.id;
            let passwordField;
            let eyeIcon;
            let eyeOffIcon;
            
            // Determine which password field to toggle
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
            
            // Toggle password field type
            const isPassword = passwordField.type === 'password';
            passwordField.type = isPassword ? 'text' : 'password';
            
            // Toggle icon visibility
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
 * Initialize form feedback and validation
 */
function initFormFeedback() {
    const forms = document.querySelectorAll('form[id$="-form"]');
    
    forms.forEach(form => {
        // Store original button state on page load
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton && !submitButton.getAttribute('data-original-text')) {
            submitButton.setAttribute('data-original-text', submitButton.textContent.trim());
        }
        
        // Only change button AFTER form actually submits (validation passed)
        // Listen for the actual submit event AFTER validation
        form.addEventListener('submit', function(e) {
            // This only runs if form wasn't prevented by validation
            const submitButton = form.querySelector('button[type="submit"]');
            
            if (submitButton) {
                // Store original text if not already stored
                if (!submitButton.getAttribute('data-original-text')) {
                    submitButton.setAttribute('data-original-text', submitButton.textContent.trim());
                }
                
                const originalText = submitButton.getAttribute('data-original-text') || submitButton.textContent.trim();
                
                // Change button immediately since validation passed
                submitButton.disabled = true;
                submitButton.textContent = originalText.includes('Sign') 
                    ? 'Signing in...' 
                    : originalText.includes('Create') 
                        ? 'Creating...' 
                        : originalText.includes('Reset')
                            ? 'Resetting...'
                            : 'Processing...';
            }
        }, false); // Use bubble phase - only runs if validation didn't prevent
    });
    
    // Clear validation errors on input
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const errorMessage = this.parentElement.querySelector('.text-\\[var\\(--color-error\\)\\]');
            if (errorMessage && this.value.trim() !== '') {
                // Remove error styling when user starts typing
                this.classList.remove('border-[var(--color-error)]');
                this.classList.add('border-[var(--color-border-primary)]');
            }
        });
    });
}

/**
 * Show form error message
 */
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.classList.add('border-[var(--color-error)]');
    
    // Remove existing error message
    const existingError = field.parentElement.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    const errorDiv = document.createElement('p');
    errorDiv.className = 'mt-1 text-sm text-[var(--color-error)] error-message';
    errorDiv.textContent = message;
    field.parentElement.appendChild(errorDiv);
}

/**
 * Clear form error
 */
function clearError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.classList.remove('border-[var(--color-error)]');
    
    const errorMessage = field.parentElement.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

