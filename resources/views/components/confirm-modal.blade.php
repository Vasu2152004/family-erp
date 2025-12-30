@props([
    'id' => 'confirm-modal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'confirmVariant' => 'primary', // 'primary' or 'danger'
])

<div 
    id="{{ $id }}" 
    class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    aria-describedby="{{ $id }}-message"
>
    <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-xl border border-[var(--color-border-primary)] max-w-md w-full p-6 animate-fade-in">
        <div class="flex items-center justify-between mb-4">
            <h3 id="{{ $id }}-title" class="text-xl font-bold text-[var(--color-text-primary)]">
                {{ $title }}
            </h3>
            <button 
                type="button" 
                class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors"
                onclick="closeConfirmModal('{{ $id }}')"
                aria-label="Close"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <p id="{{ $id }}-message" class="text-[var(--color-text-secondary)] mb-6">
            {{ $message }}
        </p>
        
        <div class="flex justify-end gap-3">
            <button 
                type="button" 
                onclick="closeConfirmModal('{{ $id }}')"
                class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors font-medium"
            >
                {{ $cancelText }}
            </button>
            <button 
                type="button" 
                id="{{ $id }}-confirm-btn"
                class="px-4 py-2 rounded-lg font-medium transition-colors
                    @if($confirmVariant === 'danger')
                        bg-red-600 hover:bg-red-700 text-white
                    @else
                        bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white
                    @endif
                "
            >
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>

