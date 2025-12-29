<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="md:col-span-2">
        <x-label for="title" required>Task Title</x-label>
        <x-input type="text" name="title" id="title" value="{{ old('title', $task ? $task->title : '') }}" required placeholder="e.g., Clean the kitchen, Take out trash" class="mt-1" />
        <x-error-message field="title" />
    </div>

    <div class="md:col-span-2">
        <x-label for="description">Description</x-label>
        <x-textarea name="description" id="description" rows="3" value="{{ old('description', $task ? $task->description : '') }}" placeholder="Optional task details..." class="mt-1" />
        <x-error-message field="description" />
    </div>

    <div>
        <x-label for="frequency" required>Frequency</x-label>
        <x-select name="frequency" id="frequency" required class="mt-1">
            <option value="once" {{ old('frequency', $task ? $task->frequency : 'once') == 'once' ? 'selected' : '' }}>Once</option>
            <option value="daily" {{ old('frequency') == 'daily' ? 'selected' : '' }}>Daily</option>
            <option value="weekly" {{ old('frequency') == 'weekly' ? 'selected' : '' }}>Weekly</option>
            <option value="monthly" {{ old('frequency') == 'monthly' ? 'selected' : '' }}>Monthly</option>
        </x-select>
        <x-error-message field="frequency" />
    </div>

    <div>
        <x-label for="family_member_id">Assign To</x-label>
        <x-select name="family_member_id" id="family_member_id" class="mt-1">
            <option value="">Unassigned</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" {{ old('family_member_id', $task ? $task->family_member_id : '') == $member->id ? 'selected' : '' }}>
                    {{ $member->first_name }} {{ $member->last_name }}
                </option>
            @endforeach
        </x-select>
        <x-error-message field="family_member_id" />
    </div>

    <!-- Conditional fields based on frequency -->
    <div id="due_date_field" class="hidden">
        <x-label for="due_date" required>Due Date</x-label>
        <x-input type="date" name="due_date" id="due_date" value="{{ old('due_date', $task && $task->due_date ? $task->due_date->format('Y-m-d') : '') }}" class="mt-1" />
        <x-error-message field="due_date" />
    </div>

    <div id="recurrence_day_field" class="hidden">
        <x-label for="recurrence_day" required>Recurrence Day</x-label>
        <x-select name="recurrence_day" id="recurrence_day" class="mt-1">
            <option value="">Select day</option>
        </x-select>
        <x-error-message field="recurrence_day" />
    </div>

    <div>
        <x-label for="recurrence_time">Recurrence Time (Optional)</x-label>
        <x-input type="time" name="recurrence_time" id="recurrence_time" value="{{ old('recurrence_time', $task && $task->recurrence_time ? \Carbon\Carbon::parse($task->recurrence_time)->format('H:i') : '') }}" class="mt-1" />
        <x-error-message field="recurrence_time" />
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const frequencySelect = document.getElementById('frequency');
    const dueDateField = document.getElementById('due_date_field');
    const recurrenceDayField = document.getElementById('recurrence_day_field');
    const recurrenceDaySelect = document.getElementById('recurrence_day');
    const currentFrequency = '{{ old('frequency', $task ? $task->frequency : 'once') }}';
    const currentRecurrenceDay = '{{ old('recurrence_day', $task ? $task->recurrence_day : '') }}';

    function updateFields() {
        const frequency = frequencySelect.value;
        
        // Hide all conditional fields
        dueDateField.classList.add('hidden');
        recurrenceDayField.classList.add('hidden');
        document.getElementById('due_date').removeAttribute('required');
        document.getElementById('recurrence_day').removeAttribute('required');

        if (frequency === 'once') {
            dueDateField.classList.remove('hidden');
            document.getElementById('due_date').setAttribute('required', 'required');
        } else if (frequency === 'weekly') {
            recurrenceDayField.classList.remove('hidden');
            document.getElementById('recurrence_day').setAttribute('required', 'required');
            
            // Populate with days of week
            recurrenceDaySelect.innerHTML = '<option value="">Select day</option>';
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            days.forEach((day, index) => {
                const option = document.createElement('option');
                option.value = (index + 1).toString();
                option.textContent = day;
                if (currentRecurrenceDay == (index + 1)) {
                    option.selected = true;
                }
                recurrenceDaySelect.appendChild(option);
            });
        } else if (frequency === 'monthly') {
            recurrenceDayField.classList.remove('hidden');
            document.getElementById('recurrence_day').setAttribute('required', 'required');
            
            // Populate with days of month
            recurrenceDaySelect.innerHTML = '<option value="">Select day</option>';
            for (let i = 1; i <= 31; i++) {
                const option = document.createElement('option');
                option.value = i.toString();
                option.textContent = i.toString();
                if (currentRecurrenceDay == i) {
                    option.selected = true;
                }
                recurrenceDaySelect.appendChild(option);
            }
        }
    }

    frequencySelect.addEventListener('change', updateFields);
    updateFields(); // Initialize on page load
});
</script>

