<div class="grid grid-cols-1 gap-6">
    <div>
        <x-label for="title" required>Title</x-label>
        <x-input type="text" name="title" id="title" value="{{ old('title', $note->title ?? '') }}" required class="mt-1" placeholder="Meeting notes, Grocery list..." />
        <x-error-message field="title" />
    </div>

    <div>
        <x-label for="body">Body</x-label>
        <x-textarea name="body" id="body" rows="6" value="{{ old('body', $note->body ?? '') }}" placeholder="Write your note here..." class="mt-1" />
        <x-error-message field="body" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-label for="visibility" required>Visibility</x-label>
            <x-select name="visibility" id="visibility" required class="mt-1">
                <option value="shared" @selected(old('visibility', $note->visibility ?? 'shared') === 'shared')>Shared (visible to all family)</option>
                <option value="private" @selected(old('visibility', $note->visibility ?? '') === 'private')>Private (creator + owner/admin)</option>
                <option value="locked" @selected(old('visibility', $note->visibility ?? '') === 'locked')>Locked (PIN required by everyone)</option>
            </x-select>
            <x-error-message field="visibility" />
        </div>

        <div>
            <x-label for="pin">PIN (required for locked)</x-label>
            <x-input type="password" name="pin" id="pin" autocomplete="new-password" class="mt-1" placeholder="Set or change PIN" disabled />
            <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                Required when creating a locked note or switching to locked. Enter to change PIN; leave blank to keep existing when not changing visibility.
            </p>
            <x-error-message field="pin" />
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const visibilitySelect = document.getElementById('visibility');
    const pinInput = document.getElementById('pin');
    const currentVisibility = '{{ old('visibility', $note->visibility ?? 'shared') }}';

    function updatePinField() {
        const visibility = visibilitySelect.value;
        if (visibility === 'shared') {
            pinInput.disabled = true;
            pinInput.value = '';
            pinInput.removeAttribute('required');
        } else if (visibility === 'locked') {
            pinInput.disabled = false;
            pinInput.setAttribute('required', 'required');
        } else {
            pinInput.disabled = false;
            pinInput.removeAttribute('required');
        }
    }

    visibilitySelect.addEventListener('change', updatePinField);
    updatePinField(); // Initialize on page load
});
</script>

