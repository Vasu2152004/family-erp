<x-app-layout title="Upload Document">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Documents', 'url' => route('families.documents.index', ['family' => $family->id])],
            ['label' => 'Upload'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Add Document</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">Store passports, IDs, property papers, and insurance documents securely.</p>
                </div>
                <a href="{{ route('families.documents.index', ['family' => $family->id]) }}" class="text-[var(--color-primary)] hover:underline text-sm">Back to list</a>
            </div>

            <x-form method="POST" action="{{ route('families.documents.store', ['family' => $family->id]) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-form-field label="Title" labelFor="title" required>
                    <x-input name="title" id="title" required placeholder="e.g., Passport - John" />
                    <x-error-message field="title" />
                </x-form-field>

                <x-form-field label="Document Type" labelFor="document_type" required>
                    <div class="flex gap-2">
                        <select name="document_type" id="document_type" class="flex-1 rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" required>
                            <optgroup label="Built-in Types">
                                @foreach($documentTypes as $type)
                                    @if($type['is_system'] ?? false)
                                        <option value="{{ $type['value'] }}" data-supports-expiry="{{ $type['supports_expiry'] ? '1' : '0' }}">{{ $type['label'] }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                            @if(collect($documentTypes)->where('is_system', false)->count() > 0)
                                <optgroup label="Custom Types">
                                    @foreach($documentTypes as $type)
                                        @if(!($type['is_system'] ?? false))
                                            <option value="{{ $type['value'] }}" data-supports-expiry="{{ $type['supports_expiry'] ? '1' : '0' }}">{{ $type['label'] }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <button type="button" id="add-custom-type-btn" class="px-4 py-2.5 rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)] transition-colors text-sm whitespace-nowrap">
                            + Add Type
                        </button>
                    </div>
                    <x-error-message field="document_type" />
                </x-form-field>

                <x-form-field label="Linked Member (optional)" labelFor="family_member_id">
                    <select name="family_member_id" id="family_member_id" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">Select member</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->last_name }}</option>
                        @endforeach
                    </select>
                    <x-error-message field="family_member_id" />
                </x-form-field>

                <x-form-field label="Expires At (optional)" labelFor="expires_at" helpText="Set expiry for passports, driving licenses, and insurance to receive reminders.">
                    <input type="date" name="expires_at" id="expires_at" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
                    <x-error-message field="expires_at" />
                </x-form-field>

                <x-form-field label="Upload File" labelFor="file" required>
                    <input type="file" name="file" id="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" required>
                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">PDF, JPG, or PNG up to 12MB.</p>
                    <x-error-message field="file" />
                </x-form-field>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-lg p-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="is_sensitive" value="1" class="mt-1 h-4 w-4 rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]" />
                        <span class="text-sm text-[var(--color-text-primary)]">
                            Mark as sensitive (password required)
                        </span>
                    </label>
                    <div>
                        <label for="password" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Document Password</label>
                        <input type="password" name="password" id="password" placeholder="Required when sensitive" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <x-error-message field="password" />
                    </div>
                </div>

                <div class="md:col-span-2 flex justify-end gap-3">
                    <a href="{{ route('families.documents.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)]">Cancel</a>
                    <x-button type="submit" variant="primary" size="md">Save Document</x-button>
                </div>
            </x-form>
        </div>
    </div>

    <!-- Add Custom Document Type Modal -->
    <div id="custom-type-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-xl border border-[var(--color-border-primary)] max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Add Custom Document Type</h3>
                <button type="button" id="close-modal-btn" class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="custom-type-form" class="space-y-4">
                @csrf
                <div>
                    <label for="custom_type_name" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Type Name</label>
                    <input type="text" name="name" id="custom_type_name" required class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="e.g., Birth Certificate">
                </div>
                <div>
                    <label for="custom_type_description" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">Description (optional)</label>
                    <textarea name="description" id="custom_type_description" rows="2" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Brief description"></textarea>
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="supports_expiry" id="custom_type_supports_expiry" class="h-4 w-4 rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                    <span class="text-sm text-[var(--color-text-primary)]">Supports expiry date (for reminders)</span>
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="cancel-modal-btn" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)]">Cancel</button>
                    <x-button type="submit" variant="primary" size="md">Add Type</x-button>
                </div>
            </x-form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('custom-type-modal');
            const openBtn = document.getElementById('add-custom-type-btn');
            const closeBtn = document.getElementById('close-modal-btn');
            const cancelBtn = document.getElementById('cancel-modal-btn');
            const form = document.getElementById('custom-type-form');
            const documentTypeSelect = document.getElementById('document_type');
            const expiresAtField = document.getElementById('expires_at');

            function openModal() {
                modal.classList.remove('hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                form.reset();
            }

            openBtn?.addEventListener('click', openModal);
            closeBtn?.addEventListener('click', closeModal);
            cancelBtn?.addEventListener('click', closeModal);

            modal?.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            form?.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                data.supports_expiry = formData.has('supports_expiry') ? '1' : '0';

                try {
                    const response = await fetch('{{ route("families.document-types.store", ["family" => $family->id]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (response.ok) {
                        const option = document.createElement('option');
                        option.value = result.document_type.slug;
                        option.textContent = result.document_type.name;
                        option.setAttribute('data-supports-expiry', result.document_type.supports_expiry ? '1' : '0');

                        const customGroup = documentTypeSelect.querySelector('optgroup[label="Custom Types"]');
                        if (customGroup) {
                            customGroup.appendChild(option);
                        } else {
                            const group = document.createElement('optgroup');
                            group.label = 'Custom Types';
                            group.appendChild(option);
                            documentTypeSelect.appendChild(group);
                        }

                        option.selected = true;
                        updateExpiryFieldVisibility();
                        closeModal();
                    } else {
                        alert(result.message || 'Failed to create document type');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                }
            });

            function updateExpiryFieldVisibility() {
                const selectedOption = documentTypeSelect.options[documentTypeSelect.selectedIndex];
                const supportsExpiry = selectedOption?.getAttribute('data-supports-expiry') === '1';
                const expiresAtContainer = expiresAtField?.closest('.form-field') || expiresAtField?.parentElement;
                if (expiresAtContainer) {
                    expiresAtContainer.style.display = supportsExpiry ? 'block' : 'none';
                }
            }

            documentTypeSelect?.addEventListener('change', updateExpiryFieldVisibility);
            updateExpiryFieldVisibility();
        });
    </script>
    @endpush

</x-app-layout>

