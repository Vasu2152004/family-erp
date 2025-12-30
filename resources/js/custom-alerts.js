/**
 * Custom Alerts and Confirmations System
 * Replaces browser default alert(), confirm(), and prompt() with custom UI components
 */

/**
 * Show a custom alert notification
 * @param {string} message - The message to display
 * @param {string} type - Alert type: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Auto-dismiss duration in milliseconds (0 = no auto-dismiss)
 */
function showAlert(message, type = 'info', duration = 5000) {
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

    const classes = `p-4 rounded-lg border animate-fade-in ${typeClasses[type] || typeClasses.info}`;
    alertElement.className = classes;

    // Create alert content
    alertElement.innerHTML = `
        <div class="flex items-start">
            <div class="flex-1">
                ${escapeHtml(message)}
            </div>
            <button 
                type="button" 
                class="ml-4 text-current opacity-70 hover:opacity-100 transition-opacity"
                onclick="dismissAlert('${alertId}')"
                aria-label="Close"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;

    // Add to container
    alertContainer.appendChild(alertElement);

    // Auto-dismiss if duration is set
    if (duration > 0) {
        setTimeout(() => {
            dismissAlert(alertId);
        }, duration);
    }

    // Scroll into view if needed
    alertElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Dismiss an alert
 * @param {string} alertId - The ID of the alert to dismiss
 */
function dismissAlert(alertId) {
    const alertElement = document.getElementById(alertId);
    if (alertElement) {
        alertElement.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        alertElement.style.opacity = '0';
        alertElement.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            alertElement.remove();
        }, 300);
    }
}

/**
 * Show a custom confirmation modal
 * @param {Object} options - Configuration options
 * @param {string} options.title - Modal title
 * @param {string} options.message - Modal message
 * @param {string} options.confirmText - Confirm button text
 * @param {string} options.cancelText - Cancel button text
 * @param {string} options.variant - Button variant: 'primary' or 'danger'
 * @returns {Promise} - Resolves if confirmed, rejects if cancelled
 */
function showConfirm(options = {}) {
    const {
        title = 'Confirm Action',
        message = 'Are you sure you want to proceed?',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        variant = 'primary'
    } = options;

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
    const modalHTML = `
        <div 
            id="${modalId}" 
            class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="${modalId}-title"
            aria-describedby="${modalId}-message"
        >
            <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-xl border border-[var(--color-border-primary)] max-w-md w-full p-6 animate-fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="${modalId}-title" class="text-xl font-bold text-[var(--color-text-primary)]">
                        ${escapeHtml(title)}
                    </h3>
                    <button 
                        type="button" 
                        class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                        onclick="closeConfirmModal('${modalId}')"
                        aria-label="Close"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <p id="${modalId}-message" class="text-[var(--color-text-secondary)] mb-6">
                    ${escapeHtml(message)}
                </p>
                
                <div class="flex justify-end gap-3">
                    <button 
                        type="button" 
                        id="${modalId}-cancel-btn"
                        class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors font-medium"
                    >
                        ${escapeHtml(cancelText)}
                    </button>
                    <button 
                        type="button" 
                        id="${modalId}-confirm-btn"
                        class="px-4 py-2 rounded-lg font-medium transition-colors
                            ${variant === 'danger' 
                                ? 'bg-red-600 hover:bg-red-700 text-white' 
                                : 'bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white'
                            }
                        "
                    >
                        ${escapeHtml(confirmText)}
                    </button>
                </div>
            </div>
        </div>
    `;

    // Insert modal
    modalContainer.innerHTML = modalHTML;
    const modal = document.getElementById(modalId);

    // Return promise
    return new Promise((resolve, reject) => {
        const confirmBtn = document.getElementById(`${modalId}-confirm-btn`);
        const cancelBtn = document.getElementById(`${modalId}-cancel-btn`);

        const cleanup = () => {
            modal.style.transition = 'opacity 0.3s ease-out';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
            }, 300);
        };

        const handleConfirm = () => {
            cleanup();
            resolve();
        };

        const handleCancel = () => {
            cleanup();
            reject(new Error('User cancelled'));
        };

        // Event listeners
        confirmBtn.addEventListener('click', handleConfirm);
        cancelBtn.addEventListener('click', handleCancel);

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                handleCancel();
            }
        });

        // Close on ESC key
        const handleEsc = (e) => {
            if (e.key === 'Escape') {
                handleCancel();
                document.removeEventListener('keydown', handleEsc);
            }
        };
        document.addEventListener('keydown', handleEsc);

        // Focus on confirm button
        setTimeout(() => {
            confirmBtn.focus();
        }, 100);
    });
}

/**
 * Close a confirmation modal
 * @param {string} modalId - The ID of the modal to close
 */
function closeConfirmModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.transition = 'opacity 0.3s ease-out';
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} - Escaped text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Initialize form confirmation handlers
 * Handles forms with data-confirm attribute
 */
function initFormConfirmations() {
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
            }).then(() => {
                // User confirmed - submit the form
                form.removeAttribute('data-confirm');
                form.submit();
            }).catch(() => {
                // User cancelled - do nothing
            });
        }
    }, true); // Use capture phase to catch before other handlers
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFormConfirmations);
} else {
    initFormConfirmations();
}

// Make functions globally available
window.showAlert = showAlert;
window.showConfirm = showConfirm;
window.dismissAlert = dismissAlert;
window.closeConfirmModal = closeConfirmModal;

