<x-app-layout title="Medicine Details: {{ $medicine->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Medicines', 'url' => route('families.medicines.index', ['family' => $family->id])],
            ['label' => $medicine->name],
        ]" />

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $medicine->name }}</h2>
                    @if($medicine->manufacturer)
                        <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $medicine->manufacturer }}</p>
                    @endif
                </div>
                <div class="flex gap-2 flex-wrap">
                    @can('update', $medicine)
                        <a href="{{ route('families.medicines.edit', ['family' => $family->id, 'medicine' => $medicine->id]) }}">
                            <x-button variant="primary" size="md">Edit</x-button>
                        </a>
                    @endcan
                    @can('delete', $medicine)
                        <x-form 
                            method="POST" 
                            action="{{ route('families.medicines.destroy', ['family' => $family->id, 'medicine' => $medicine->id]) }}" 
                            class="inline-flex"
                            data-confirm="Delete this medicine?"
                            data-confirm-title="Delete Medicine"
                            data-confirm-variant="danger"
                        >
                            @csrf
                            @method('DELETE')
                            <x-button variant="ghost" size="md" class="text-red-600 hover:text-red-700">Delete</x-button>
                        </x-form>
                    @endcan
                    <a href="{{ route('families.medicines.index', ['family' => $family->id]) }}">
                        <x-button variant="outline" size="md">Back</x-button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="text-sm text-[var(--color-text-secondary)]">Quantity</label>
                    @php
                        $qty = $medicine->quantity == (int)$medicine->quantity ? (int)$medicine->quantity : number_format((float)$medicine->quantity, 2);
                    @endphp
                    <p class="text-[var(--color-text-primary)] font-medium">{{ $qty }} {{ $medicine->unit }}</p>
                </div>
                @if($medicine->min_stock_level)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Minimum Stock Level</label>
                        @php
                            $minQty = $medicine->min_stock_level == (int)$medicine->min_stock_level ? (int)$medicine->min_stock_level : number_format((float)$medicine->min_stock_level, 2);
                        @endphp
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $minQty }} {{ $medicine->unit }}</p>
                    </div>
                @endif
                @if($medicine->expiry_date)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Expiry Date</label>
                        @php
                            $daysUntilExpiry = (int)now()->diffInDays($medicine->expiry_date, false);
                        @endphp
                        <p class="text-[var(--color-text-primary)] font-medium">
                            {{ $medicine->expiry_date->format('M d, Y') }}
                            @if($daysUntilExpiry < 0)
                                <span class="ml-2 text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">Expired</span>
                            @elseif($daysUntilExpiry <= 30)
                                <span class="ml-2 text-xs px-2 py-1 rounded-full bg-orange-100 text-orange-800">{{ $daysUntilExpiry }} days left</span>
                            @else
                                <span class="ml-2 text-xs text-[var(--color-text-secondary)]">{{ $daysUntilExpiry }} days left</span>
                            @endif
                        </p>
                    </div>
                @endif
                @if($medicine->purchase_date)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Purchase Date</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $medicine->purchase_date->format('M d, Y') }}</p>
                    </div>
                @endif
                @if($medicine->purchase_price)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Purchase Price</label>
                        <p class="text-[var(--color-text-primary)] font-medium">₹{{ number_format((float)$medicine->purchase_price, 2) }}</p>
                    </div>
                @endif
                @if($medicine->batch_number)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Batch Number</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $medicine->batch_number }}</p>
                    </div>
                @endif
                @if($medicine->familyMember)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">For Member</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $medicine->familyMember->first_name }} {{ $medicine->familyMember->last_name }}</p>
                    </div>
                @endif
                @if($medicine->prescription_file_path)
                    <div class="md:col-span-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Prescription</label>
                        <p class="text-[var(--color-text-primary)] font-medium">
                            <a href="{{ route('families.medicines.prescription.download', ['family' => $family->id, 'medicine' => $medicine->id]) }}" class="text-[var(--color-primary)] hover:underline" target="_blank">
                                📄 {{ $medicine->prescription_original_name ?? 'prescription.pdf' }}
                            </a>
                        </p>
                    </div>
                @endif
                @if($medicine->description)
                    <div class="md:col-span-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Description</label>
                        <p class="text-[var(--color-text-primary)]">{{ $medicine->description }}</p>
                    </div>
                @endif
            </div>

            <!-- Expiry Reminders Section -->
            @if($medicine->expiryReminders->count() > 0)
                <div class="mb-8 border-t border-[var(--color-border-primary)] pt-6">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">Expiry Reminders</h3>
                    <div class="space-y-2">
                        @foreach($medicine->expiryReminders as $reminder)
                            <div class="flex items-center justify-between p-3 bg-[var(--color-bg-secondary)] rounded-lg">
                                <div>
                                    <span class="text-sm text-[var(--color-text-primary)]">
                                        Reminder scheduled for {{ $reminder->remind_at->format('M d, Y') }}
                                        @if($reminder->sent_at)
                                            <span class="text-xs text-green-600 ml-2">✓ Sent</span>
                                        @else
                                            <span class="text-xs text-[var(--color-text-secondary)] ml-2">Pending</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Intake Reminders Section -->
            <div class="border-t border-[var(--color-border-primary)] pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Intake Reminders</h3>
                    @can('update', $medicine)
                        <button onclick="document.getElementById('add-intake-reminder').classList.toggle('hidden')" class="text-sm text-[var(--color-primary)] hover:underline">+ Add Reminder</button>
                    @endcan
                </div>

                @can('update', $medicine)
                    <div id="add-intake-reminder" class="{{ $errors->has('reminder_time') || $errors->has('frequency') || $errors->has('days_of_week') || $errors->has('selected_dates') ? '' : 'hidden' }} mb-4 p-4 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)]">
                        @if($errors->any())
                            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                                <ul class="list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <x-form method="POST" action="{{ route('families.medicines.intake-reminders.store', ['family' => $family->id, 'medicine' => $medicine->id]) }}" class="space-y-4" id="add-reminder-form" onsubmit="prepareReminderForm(event)">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-label for="reminder_time" required>Reminder Time</x-label>
                                    <x-input type="time" name="reminder_time" id="reminder_time" required class="mt-1" />
                                    <x-error-message field="reminder_time" />
                                </div>
                                <div>
                                    <x-label for="frequency" required>Frequency</x-label>
                                    <select name="frequency" id="frequency" required onchange="toggleFrequencyOptions()" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="custom">Custom Dates</option>
                                    </select>
                                    <x-error-message field="frequency" />
                                </div>
                                <div id="days-of-week-container" class="hidden">
                                    <x-label for="days_of_week">Days of Week</x-label>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="days_of_week[]" value="{{ $day }}" class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)]">
                                                <span class="ml-1 text-sm text-[var(--color-text-primary)] capitalize">{{ $day }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-error-message field="days_of_week" />
                                </div>
                                <div id="selected-dates-container" class="hidden md:col-span-2">
                                    <x-label for="selected_dates_input">Select Dates</x-label>
                                    <x-input type="text" name="selected_dates_input" id="selected_dates_input" placeholder="YYYY-MM-DD, YYYY-MM-DD, ..." class="mt-1" />
                                    <input type="hidden" name="selected_dates" id="selected_dates" />
                                    <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Enter dates in YYYY-MM-DD format, comma-separated (e.g., 2025-12-25, 2025-12-30)</p>
                                    <x-error-message field="selected_dates" />
                                </div>
                                <div>
                                    <x-label for="start_date">Start Date</x-label>
                                    <x-input type="date" name="start_date" id="start_date" class="mt-1" />
                                    <x-error-message field="start_date" />
                                </div>
                                <div>
                                    <x-label for="end_date">End Date</x-label>
                                    <x-input type="date" name="end_date" id="end_date" class="mt-1" />
                                    <x-error-message field="end_date" />
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <x-button type="submit" variant="primary" size="sm">Add Reminder</x-button>
                                <button type="button" onclick="document.getElementById('add-intake-reminder').classList.add('hidden')" class="px-3 py-1 text-sm rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)]">Cancel</button>
                            </div>
                        </x-form>
                    </div>
                @endcan

                @forelse($medicine->intakeReminders as $reminder)
                    <div class="flex items-center justify-between p-3 bg-[var(--color-bg-secondary)] rounded-lg mb-2">
                        <div>
                            <span class="text-sm text-[var(--color-text-primary)]">
                                <span class="font-medium capitalize">{{ $reminder->frequency }}</span>
                                @if($reminder->reminder_time)
                                    • {{ \App\Services\TimezoneService::convertUtcToIst(\Carbon\Carbon::parse('2000-01-01 ' . $reminder->reminder_time, 'UTC'))->format('h:i A') }} IST
                                @endif
                                @if($reminder->frequency === 'weekly' && $reminder->days_of_week && count($reminder->days_of_week) > 0)
                                    • {{ implode(', ', array_map('ucfirst', $reminder->days_of_week)) }}
                                @endif
                                @if($reminder->frequency === 'custom' && $reminder->selected_dates && count($reminder->selected_dates) > 0)
                                    • {{ count($reminder->selected_dates) }} custom date(s)
                                @endif
                                @if($reminder->familyMember)
                                    • For: {{ $reminder->familyMember->first_name }} {{ $reminder->familyMember->last_name }}
                                @endif
                                @if($reminder->status !== 'active')
                                    <span class="text-red-600">({{ ucfirst($reminder->status) }})</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex gap-2">
                            @can('update', $medicine)
                                <button onclick="document.getElementById('edit-reminder-{{ $reminder->id }}').classList.toggle('hidden')" class="text-xs text-[var(--color-primary)] hover:underline">Edit</button>
                                <x-form method="POST" action="{{ route('families.medicines.intake-reminders.toggle', ['family' => $family->id, 'medicine' => $medicine->id, 'reminder' => $reminder->id]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs text-[var(--color-primary)] hover:underline">
                                        {{ $reminder->status === 'active' ? 'Pause' : 'Activate' }}
                                    </button>
                                </x-form>
                            @endcan
                            @can('update', $medicine)
                                <x-form 
                                    method="POST" 
                                    action="{{ route('families.medicines.intake-reminders.destroy', ['family' => $family->id, 'medicine' => $medicine->id, 'reminder' => $reminder->id]) }}" 
                                    data-confirm="Are you sure?"
                                    data-confirm-title="Delete Reminder"
                                    data-confirm-variant="danger"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Delete</button>
                                </x-form>
                            @endcan
                        </div>
                    </div>

                    @can('update', $medicine)
                        <div id="edit-reminder-{{ $reminder->id }}" class="hidden mb-2 p-4 bg-[var(--color-bg-primary)] rounded-lg border border-[var(--color-border-primary)]">
                            <x-form method="POST" action="{{ route('families.medicines.intake-reminders.update', ['family' => $family->id, 'medicine' => $medicine->id, 'reminder' => $reminder->id]) }}" class="space-y-4">
                                @csrf
                                @method('PATCH')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-label for="reminder_time_edit_{{ $reminder->id }}" required>Reminder Time</x-label>
                                        <x-input type="time" name="reminder_time" id="reminder_time_edit_{{ $reminder->id }}" value="{{ $reminder->reminder_time ? \Carbon\Carbon::parse('2000-01-01 ' . $reminder->reminder_time)->format('H:i') : '' }}" required class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="frequency_edit_{{ $reminder->id }}" required>Frequency</x-label>
                                        <select name="frequency" id="frequency_edit_{{ $reminder->id }}" required onchange="toggleFrequencyOptionsEdit({{ $reminder->id }})" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                            <option value="daily" {{ $reminder->frequency === 'daily' ? 'selected' : '' }}>Daily</option>
                                            <option value="weekly" {{ $reminder->frequency === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                            <option value="custom" {{ $reminder->frequency === 'custom' ? 'selected' : '' }}>Custom Dates</option>
                                        </select>
                                    </div>
                                    <div id="days-of-week-container-edit-{{ $reminder->id }}" class="{{ $reminder->frequency === 'weekly' ? '' : 'hidden' }}">
                                        <x-label for="days_of_week_edit_{{ $reminder->id }}">Days of Week</x-label>
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                                <label class="flex items-center">
                                                    <input type="checkbox" name="days_of_week[]" value="{{ $day }}" {{ in_array($day, $reminder->days_of_week ?? []) ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)]">
                                                    <span class="ml-1 text-sm text-[var(--color-text-primary)] capitalize">{{ $day }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div id="selected-dates-container-edit-{{ $reminder->id }}" class="{{ $reminder->frequency === 'custom' ? '' : 'hidden' }} md:col-span-2">
                                        <x-label for="selected_dates_input_edit_{{ $reminder->id }}">Select Dates</x-label>
                                        <x-input type="text" name="selected_dates_input" id="selected_dates_input_edit_{{ $reminder->id }}" value="{{ $reminder->selected_dates ? implode(', ', $reminder->selected_dates) : '' }}" placeholder="YYYY-MM-DD, YYYY-MM-DD" class="mt-1" />
                                        <input type="hidden" name="selected_dates" id="selected_dates_edit_{{ $reminder->id }}" />
                                    </div>
                                    <div>
                                        <x-label for="start_date_edit_{{ $reminder->id }}">Start Date</x-label>
                                        <x-input type="date" name="start_date" id="start_date_edit_{{ $reminder->id }}" value="{{ $reminder->start_date ? $reminder->start_date->format('Y-m-d') : '' }}" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="end_date_edit_{{ $reminder->id }}">End Date</x-label>
                                        <x-input type="date" name="end_date" id="end_date_edit_{{ $reminder->id }}" value="{{ $reminder->end_date ? $reminder->end_date->format('Y-m-d') : '' }}" class="mt-1" />
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <x-button type="submit" variant="primary" size="sm">Update</x-button>
                                    <button type="button" onclick="document.getElementById('edit-reminder-{{ $reminder->id }}').classList.add('hidden')" class="px-3 py-1 text-sm rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)]">Cancel</button>
                                </div>
                            </x-form>
                        </div>
                    @endcan
                @empty
                    <p class="text-sm text-[var(--color-text-secondary)]">No intake reminders set.</p>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function toggleFrequencyOptions() {
            const frequency = document.getElementById('frequency').value;
            document.getElementById('days-of-week-container').classList.toggle('hidden', frequency !== 'weekly');
            document.getElementById('selected-dates-container').classList.toggle('hidden', frequency !== 'custom');
        }

        function toggleFrequencyOptionsEdit(reminderId) {
            const frequency = document.getElementById('frequency_edit_' + reminderId).value;
            document.getElementById('days-of-week-container-edit-' + reminderId).classList.toggle('hidden', frequency !== 'weekly');
            document.getElementById('selected-dates-container-edit-' + reminderId).classList.toggle('hidden', frequency !== 'custom');
        }

        // Prepare form data before submission
        function prepareReminderForm(event) {
            const form = event.target;
            const frequency = form.querySelector('#frequency').value;
            
            // Handle selected_dates for custom frequency
            if (frequency === 'custom') {
                const selectedDatesInput = form.querySelector('#selected_dates_input');
                const selectedDatesHidden = form.querySelector('#selected_dates');
                if (selectedDatesInput && selectedDatesHidden) {
                    const dates = selectedDatesInput.value.split(',').map(d => d.trim()).filter(d => d);
                    // Store as comma-separated string - the form request will handle conversion
                    selectedDatesHidden.value = dates.join(',');
                }
            } else {
                // Remove selected_dates field if not custom
                const selectedDatesHidden = form.querySelector('#selected_dates');
                if (selectedDatesHidden) {
                    selectedDatesHidden.remove();
                }
            }
            
            // Handle days_of_week for weekly frequency
            if (frequency === 'weekly') {
                const daysCheckboxes = form.querySelectorAll('input[name="days_of_week[]"]:checked');
                if (daysCheckboxes.length === 0) {
                    event.preventDefault();
                    showAlert('Please select at least one day of the week for weekly reminders.', 'warning');
                    return false;
                }
            } else {
                // Clear days_of_week if not weekly
                const daysCheckboxes = form.querySelectorAll('input[name="days_of_week[]"]');
                daysCheckboxes.forEach(cb => cb.checked = false);
            }
        }

        // Handle selected dates input for custom frequency
        document.addEventListener('DOMContentLoaded', function() {
            const selectedDatesInput = document.getElementById('selected_dates_input');
            if (selectedDatesInput) {
                selectedDatesInput.addEventListener('input', function() {
                    const dates = this.value.split(',').map(d => d.trim()).filter(d => d);
                    const hiddenInput = document.getElementById('selected_dates');
                    if (hiddenInput) {
                        hiddenInput.value = JSON.stringify(dates);
                    }
                });
            }

            // Handle edit forms - add submit handler
            @foreach($medicine->intakeReminders as $reminder)
                const editForm{{ $reminder->id }} = document.querySelector('form[action*="intake-reminders/{{ $reminder->id }}"]');
                if (editForm{{ $reminder->id }}) {
                    editForm{{ $reminder->id }}.addEventListener('submit', function(e) {
                        const frequency = this.querySelector('#frequency_edit_{{ $reminder->id }}').value;
                        if (frequency === 'custom') {
                            const selectedDatesInput = this.querySelector('#selected_dates_input_edit_{{ $reminder->id }}');
                            const selectedDatesHidden = this.querySelector('#selected_dates_edit_{{ $reminder->id }}');
                            if (selectedDatesInput && selectedDatesHidden) {
                                const dates = selectedDatesInput.value.split(',').map(d => d.trim()).filter(d => d);
                                selectedDatesHidden.value = dates.join(',');
                            }
                        }
                    });
                }
            @endforeach
        });
    </script>
</x-app-layout>


