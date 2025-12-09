document.addEventListener('DOMContentLoaded', function() {
    console.log('Transaction form script loaded');
    
    const typeSelect = document.getElementById('type');
    const typeHidden = document.querySelector('input[name="type"][type="hidden"]');
    const budgetField = document.getElementById('budget_field');
    const budgetSelect = document.getElementById('budget_id');
    const budgetHint = document.getElementById('budget_hint');
    const transferToAccountField = document.getElementById('transfer_to_account_field');
    const transferToAccountSelect = document.getElementById('transfer_to_account_id');

    console.log('Budget field element:', budgetField);
    console.log('Type select element:', typeSelect);
    console.log('Type hidden element:', typeHidden);

    // Get current type value (from select or hidden input)
    function getCurrentType() {
        if (typeSelect) {
            return typeSelect.value;
        }
        if (typeHidden) {
            return typeHidden.value;
        }
        return '';
    }

    function toggleBudgetField() {
        const currentType = getCurrentType();
        console.log('Toggle budget field - current type:', currentType);
        console.log('Budget select element:', budgetSelect);
        
        if (!budgetSelect) {
            console.error('Budget select element not found!');
            return;
        }
        
        if (currentType === 'EXPENSE') {
            console.log('Enabling budget field');
            // Remove disabled attribute completely
            budgetSelect.removeAttribute('disabled');
            // Force enable by setting property
            budgetSelect.disabled = false;
            // Update styling
            budgetSelect.style.backgroundColor = 'var(--color-bg-primary)';
            budgetSelect.style.color = 'var(--color-text-primary)';
            budgetSelect.style.cursor = 'pointer';
            budgetSelect.style.opacity = '1';
            budgetSelect.style.pointerEvents = 'auto';
            // Update first option text
            if (budgetSelect.options.length > 0 && budgetSelect.options[0].value === '') {
                budgetSelect.options[0].text = 'No specific budget (will not affect any budget)';
            }
            console.log('Budget field enabled. Disabled status:', budgetSelect.disabled);
        } else {
            console.log('Disabling budget field');
            // Disable the select
            budgetSelect.disabled = true;
            budgetSelect.setAttribute('disabled', 'disabled');
            // Update styling
            budgetSelect.style.backgroundColor = 'var(--color-bg-secondary)';
            budgetSelect.style.color = 'var(--color-text-secondary)';
            budgetSelect.style.cursor = 'not-allowed';
            budgetSelect.style.opacity = '0.6';
            budgetSelect.style.pointerEvents = 'none';
            // Reset to first option
            budgetSelect.value = '';
            // Update first option text
            if (budgetSelect.options.length > 0 && budgetSelect.options[0].value === '') {
                budgetSelect.options[0].text = 'Please select "Expense" as transaction type first';
            }
            console.log('Budget field disabled. Disabled status:', budgetSelect.disabled);
        }
        
        // Show/hide hint
        if (budgetHint) {
            if (currentType === '' || currentType === 'INCOME' || currentType === 'TRANSFER') {
                budgetHint.style.display = 'block';
            } else {
                budgetHint.style.display = 'none';
            }
        }
    }

    function toggleTransferField() {
        if (transferToAccountField && transferToAccountSelect) {
            const currentType = getCurrentType();
            if (currentType === 'TRANSFER') {
                transferToAccountField.style.display = 'block';
                transferToAccountSelect.setAttribute('required', 'required');
            } else {
                transferToAccountField.style.display = 'none';
                transferToAccountSelect.removeAttribute('required');
                transferToAccountSelect.value = '';
            }
        }
    }

    // Initial toggle (handles pre-selected types)
    toggleBudgetField();
    toggleTransferField();
    
    // Also trigger on page load if type is already set
    if (typeSelect && typeSelect.value === 'EXPENSE') {
        console.log('Type is already EXPENSE, showing budget field');
        toggleBudgetField();
    }
    if (typeHidden && typeHidden.value === 'EXPENSE') {
        console.log('Hidden type is EXPENSE, showing budget field');
        toggleBudgetField();
    }

    // Listen for changes (only if select exists)
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            console.log('Type changed to:', typeSelect.value);
            toggleBudgetField();
            toggleTransferField();
        });
    }
});
