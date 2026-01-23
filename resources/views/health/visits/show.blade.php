<x-app-layout title="Doctor Visit Details">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', ['family' => $family->id])],
            ['label' => 'Doctor Visits', 'url' => route('families.health.visits.index', ['family' => $family->id])],
            ['label' => 'Visit Details'],
        ]" />


        <!-- Visit Details -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $visit->doctor_name }}</h2>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        {{ $visit->visit_date->format('M d, Y') }}
                        @if($visit->visit_time)
                            • {{ \Carbon\Carbon::parse($visit->visit_time)->format('h:i A') }}
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('update', $visit)
                        <a href="{{ route('families.health.visits.edit', ['family' => $family->id, 'visit' => $visit->id]) }}">
                            <x-button variant="outline" size="md">Edit</x-button>
                        </a>
                    @endcan
                    <a href="{{ route('families.health.visits.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Back</x-button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($visit->status)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Status</label>
                        <p class="text-[var(--color-text-primary)] font-medium capitalize">{{ $visit->status }}</p>
                    </div>
                @endif

                @if($visit->clinic_name)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Clinic/Hospital</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $visit->clinic_name }}</p>
                    </div>
                @endif

                @if($visit->specialization)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Specialization</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $visit->specialization }}</p>
                    </div>
                @endif

                @if($visit->visit_type)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Visit Type</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ ucfirst(str_replace('_', ' ', $visit->visit_type)) }}</p>
                    </div>
                @endif

                @if($visit->familyMember)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Patient</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $visit->familyMember->first_name }} {{ $visit->familyMember->last_name }}</p>
                    </div>
                @endif

                @if($visit->next_visit_date)
                    <div>
                        <label class="text-sm text-[var(--color-text-secondary)]">Next Visit</label>
                        <p class="text-[var(--color-text-primary)] font-medium">{{ $visit->next_visit_date->format('M d, Y') }}</p>
                    </div>
                @endif
            </div>

            @if($visit->chief_complaint)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Chief Complaint</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $visit->chief_complaint }}</p>
                </div>
            @endif

            @if($visit->examination_findings)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Examination Findings</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $visit->examination_findings }}</p>
                </div>
            @endif

            @if($visit->diagnosis)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Diagnosis</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $visit->diagnosis }}</p>
                </div>
            @endif

            @if($visit->treatment_given)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Treatment Given</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $visit->treatment_given }}</p>
                </div>
            @endif

            @if($visit->notes)
                <div class="mt-6">
                    <label class="text-sm text-[var(--color-text-secondary)]">Notes</label>
                    <p class="text-[var(--color-text-primary)] mt-1 whitespace-pre-wrap">{{ $visit->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Prescriptions -->
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Prescriptions</h3>
                @can('create', \App\Models\Prescription::class)
                    <button onclick="document.getElementById('add-prescription-form').classList.toggle('hidden')" class="px-4 py-2 bg-[var(--color-primary)] text-white rounded-lg hover:opacity-90 transition-opacity">
                        Add Prescription
                    </button>
                @endcan
            </div>

            <!-- Add Prescription Form -->
            @can('create', \App\Models\Prescription::class)
                <div id="add-prescription-form" class="hidden mb-6 p-4 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)]">
                    <x-form method="POST" action="{{ route('families.health.visits.prescriptions.store', ['family' => $family->id, 'visit' => $visit->id]) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-label for="medication_name" required>Medication Name</x-label>
                                        <x-input type="text" name="medication_name" id="medication_name" required class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="dosage">Dosage</x-label>
                                        <x-input type="text" name="dosage" id="dosage" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="frequency">Frequency</x-label>
                                        <x-input type="text" name="frequency" id="frequency" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="start_date">Start Date</x-label>
                                        <x-input type="date" name="start_date" id="start_date" class="mt-1" />
                                    </div>
                            <div>
                                <x-label for="end_date">End Date</x-label>
                                <x-input type="date" name="end_date" id="end_date" class="mt-1" />
                            </div>
                            <div>
                                <x-label for="status">Status</x-label>
                                <select name="status" id="status" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="stopped">Stopped</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <x-label for="instructions">Instructions</x-label>
                                <textarea name="instructions" id="instructions" rows="2" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <x-label for="attachment">Prescription File (PDF/PNG/JPG)</x-label>
                                <input type="file" name="attachment" id="attachment" accept=".pdf,.png,.jpg,.jpeg" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <x-button type="submit" variant="primary" size="md">Add Prescription</x-button>
                            <button type="button" onclick="document.getElementById('add-prescription-form').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)]">Cancel</button>
                        </div>
                    </x-form>
                </div>
            @endcan

            @forelse($visit->prescriptions as $prescription)
                <div class="border-b border-[var(--color-border-primary)] pb-6 mb-6 last:border-0 last:pb-0 last:mb-0">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h4 class="font-semibold text-[var(--color-text-primary)] text-lg">{{ $prescription->medication_name }}</h4>
                            <p class="text-sm text-[var(--color-text-secondary)] mt-1">
                                <span class="font-medium">Dosage:</span> {{ $prescription->dosage }} • 
                                <span class="font-medium">Frequency:</span> {{ $prescription->frequency }}
                            </p>
                            <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                {{ $prescription->start_date->format('M d, Y') }}
                                @if($prescription->end_date)
                                    - {{ $prescription->end_date->format('M d, Y') }}
                                @endif
                                • <span class="capitalize">{{ $prescription->status }}</span>
                            </p>
                            @if($prescription->instructions)
                                <p class="text-sm text-[var(--color-text-primary)] mt-2">{{ $prescription->instructions }}</p>
                            @endif
                            @if($prescription->file_path)
                                <a href="{{ route('families.health.visits.prescriptions.download', ['family' => $family->id, 'visit' => $visit->id, 'prescription' => $prescription->id]) }}" class="inline-flex items-center mt-2 text-[var(--color-primary)] hover:underline text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download Prescription
                                </a>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            @can('update', $prescription)
                                <button onclick="document.getElementById('edit-prescription-{{ $prescription->id }}').classList.toggle('hidden')">
                                    <x-button variant="outline" size="sm">Edit</x-button>
                                </button>
                            @endcan
                            @can('delete', $prescription)
                                <x-form 
                                    method="POST" 
                                    action="{{ route('families.health.visits.prescriptions.destroy', ['family' => $family->id, 'visit' => $visit->id, 'prescription' => $prescription->id]) }}" 
                                    class="inline"
                                    data-confirm="Are you sure?"
                                    data-confirm-title="Delete Prescription"
                                    data-confirm-variant="danger"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger-outline" size="sm">Delete</x-button>
                                </x-form>
                            @endcan
                        </div>
                    </div>

                    <!-- Edit Prescription Form -->
                    @can('update', $prescription)
                        <div id="edit-prescription-{{ $prescription->id }}" class="hidden mb-4 p-4 bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)]">
                            <x-form method="POST" action="{{ route('families.health.visits.prescriptions.update', ['family' => $family->id, 'visit' => $visit->id, 'prescription' => $prescription->id]) }}" enctype="multipart/form-data" class="space-y-4">
                                @csrf
                                @method('PATCH')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-label for="medication_name_{{ $prescription->id }}" required>Medication Name</x-label>
                                        <x-input type="text" name="medication_name" id="medication_name_{{ $prescription->id }}" value="{{ $prescription->medication_name }}" required class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="dosage_{{ $prescription->id }}">Dosage</x-label>
                                        <x-input type="text" name="dosage" id="dosage_{{ $prescription->id }}" value="{{ $prescription->dosage }}" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="frequency_{{ $prescription->id }}">Frequency</x-label>
                                        <x-input type="text" name="frequency" id="frequency_{{ $prescription->id }}" value="{{ $prescription->frequency }}" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="start_date_{{ $prescription->id }}">Start Date</x-label>
                                        <x-input type="date" name="start_date" id="start_date_{{ $prescription->id }}" value="{{ $prescription->start_date?->format('Y-m-d') }}" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="end_date_{{ $prescription->id }}">End Date</x-label>
                                        <x-input type="date" name="end_date" id="end_date_{{ $prescription->id }}" value="{{ $prescription->end_date?->format('Y-m-d') }}" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-label for="status_{{ $prescription->id }}">Status</x-label>
                                        <select name="status" id="status_{{ $prescription->id }}" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">
                                            <option value="active" {{ $prescription->status == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="completed" {{ $prescription->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="stopped" {{ $prescription->status == 'stopped' ? 'selected' : '' }}>Stopped</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-label for="instructions_{{ $prescription->id }}">Instructions</x-label>
                                        <textarea name="instructions" id="instructions_{{ $prescription->id }}" rows="2" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">{{ $prescription->instructions }}</textarea>
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-label for="attachment_{{ $prescription->id }}">Update Prescription File (PDF/PNG/JPG)</x-label>
                                        <input type="file" name="attachment" id="attachment_{{ $prescription->id }}" accept=".pdf,.png,.jpg,.jpeg" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <x-button type="submit" variant="primary" size="md">Update</x-button>
                                    <button type="button" onclick="document.getElementById('edit-prescription-{{ $prescription->id }}').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)]">Cancel</button>
                                </div>
                            </x-form>
                        </div>
                    @endcan

                    <!-- Medicine Reminders -->
                    <div class="mt-4 pl-4 border-l-2 border-[var(--color-border-primary)]">
                        <div class="flex items-center justify-between mb-2">
                            <h5 class="font-medium text-[var(--color-text-primary)]">Reminders</h5>
                            @can('create', \App\Models\MedicineReminder::class)
                                <button onclick="document.getElementById('add-reminder-{{ $prescription->id }}').classList.toggle('hidden')" class="text-xs text-[var(--color-primary)] hover:underline">Add Reminder</button>
                            @endcan
                        </div>

                        @can('create', \App\Models\MedicineReminder::class)
                            <div id="add-reminder-{{ $prescription->id }}" class="hidden mb-3 p-3 bg-[var(--color-bg-primary)] rounded border border-[var(--color-border-primary)]">
                                <x-form method="POST" action="{{ route('families.health.visits.prescriptions.reminders.store', ['family' => $family->id, 'visit' => $visit->id, 'prescription' => $prescription->id]) }}" class="space-y-3">
                                    @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <x-label for="frequency_{{ $prescription->id }}" required>Frequency</x-label>
                                            <select name="frequency" id="frequency_{{ $prescription->id }}" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">
                                                <option value="once">Once</option>
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly</option>
                                            </select>
                                        </div>
                                        <div>
                                            <x-label for="reminder_time_{{ $prescription->id }}">Reminder Time</x-label>
                                            <x-input type="time" name="reminder_time" id="reminder_time_{{ $prescription->id }}" class="mt-1" />
                                        </div>
                                        <div>
                                            <x-label for="start_date_{{ $prescription->id }}" required>Start Date</x-label>
                                            <x-input type="date" name="start_date" id="start_date_{{ $prescription->id }}" required class="mt-1" />
                                        </div>
                                        <div>
                                            <x-label for="end_date_{{ $prescription->id }}">End Date</x-label>
                                            <x-input type="date" name="end_date" id="end_date_{{ $prescription->id }}" class="mt-1" />
                                        </div>
                                        <div>
                                            <x-label for="days_of_week_{{ $prescription->id }}">Days of Week (for weekly frequency)</x-label>
                                            <div class="mt-1 flex flex-wrap gap-2">
                                                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                                    <label class="flex items-center">
                                                        <input type="checkbox" name="days_of_week[]" value="{{ $day }}" class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)]">
                                                        <span class="ml-1 text-sm text-[var(--color-text-primary)] capitalize">{{ $day }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div>
                                            <x-label for="status_{{ $prescription->id }}">Status</x-label>
                                            <select name="status" id="status_{{ $prescription->id }}" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">
                                                <option value="active">Active</option>
                                                <option value="paused">Paused</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <x-button type="submit" variant="primary" size="sm">Add</x-button>
                                        <button type="button" onclick="document.getElementById('add-reminder-{{ $prescription->id }}').classList.add('hidden')" class="px-3 py-1 text-sm rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)]">Cancel</button>
                                    </div>
                                </x-form>
                            </div>
                        @endcan

                        @forelse($prescription->reminders as $reminder)
                            <div class="flex items-center justify-between p-2 bg-[var(--color-bg-secondary)] rounded mb-2">
                                <div>
                                    <span class="text-sm text-[var(--color-text-primary)]">
                                        <span class="font-medium capitalize">{{ $reminder->frequency }}</span>
                                        @if($reminder->reminder_time)
                                            • {{ \App\Services\TimezoneService::convertUtcToIst($reminder->reminder_time)->format('h:i A') }} IST
                                        @endif
                                        @if($reminder->days_of_week && count($reminder->days_of_week) > 0)
                                            • {{ implode(', ', array_map('ucfirst', $reminder->days_of_week)) }}
                                        @endif
                                        @if($reminder->start_date)
                                            • From {{ $reminder->start_date->format('M d, Y') }}
                                        @endif
                                        @if($reminder->end_date)
                                            • Until {{ $reminder->end_date->format('M d, Y') }}
                                        @endif
                                        @if($reminder->status !== 'active')
                                            <span class="text-red-600">({{ ucfirst($reminder->status) }})</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    @can('update', $reminder)
                                        <button onclick="document.getElementById('edit-reminder-{{ $reminder->id }}').classList.toggle('hidden')">
                                            <x-button variant="outline" size="sm" class="text-xs">Edit</x-button>
                                        </button>
                                    @endcan
                                    @can('delete', $reminder)
                                        <x-form 
                                            method="POST" 
                                            action="{{ route('families.health.visits.prescriptions.reminders.destroy', ['family' => $family->id, 'visit' => $visit->id, 'prescription' => $prescription->id, 'reminder' => $reminder->id]) }}" 
                                            class="inline"
                                            data-confirm="Are you sure?"
                                            data-confirm-title="Delete Reminder"
                                            data-confirm-variant="danger"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="danger-outline" size="sm" class="text-xs">Delete</x-button>
                                        </x-form>
                                    @endcan
                                </div>
                            </div>

                            @can('update', $reminder)
                                <div id="edit-reminder-{{ $reminder->id }}" class="hidden mb-2 p-3 bg-[var(--color-bg-primary)] rounded border border-[var(--color-border-primary)]">
                                    <x-form method="POST" action="{{ route('families.health.visits.prescriptions.reminders.update', ['family' => $family->id, 'visit' => $visit->id, 'prescription' => $prescription->id, 'reminder' => $reminder->id]) }}" class="space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <x-label for="frequency_edit_{{ $reminder->id }}" required>Frequency</x-label>
                                                <select name="frequency" id="frequency_edit_{{ $reminder->id }}" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">
                                                    <option value="once" {{ $reminder->frequency == 'once' ? 'selected' : '' }}>Once</option>
                                                    <option value="daily" {{ $reminder->frequency == 'daily' ? 'selected' : '' }}>Daily</option>
                                                    <option value="weekly" {{ $reminder->frequency == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                </select>
                                            </div>
                                            <div>
                                                <x-label for="reminder_time_edit_{{ $reminder->id }}">Reminder Time</x-label>
                                                <x-input type="time" name="reminder_time" id="reminder_time_edit_{{ $reminder->id }}" value="{{ $reminder->reminder_time ? \Carbon\Carbon::parse($reminder->reminder_time)->format('H:i') : '' }}" class="mt-1" />
                                            </div>
                                            <div>
                                                <x-label for="start_date_edit_{{ $reminder->id }}" required>Start Date</x-label>
                                                <x-input type="date" name="start_date" id="start_date_edit_{{ $reminder->id }}" value="{{ $reminder->start_date ? $reminder->start_date->format('Y-m-d') : '' }}" required class="mt-1" />
                                            </div>
                                            <div>
                                                <x-label for="end_date_edit_{{ $reminder->id }}">End Date</x-label>
                                                <x-input type="date" name="end_date" id="end_date_edit_{{ $reminder->id }}" value="{{ $reminder->end_date ? $reminder->end_date->format('Y-m-d') : '' }}" class="mt-1" />
                                            </div>
                                            <div>
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
                                            <div>
                                                <x-label for="status_edit_{{ $reminder->id }}">Status</x-label>
                                                <select name="status" id="status_edit_{{ $reminder->id }}" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)]">
                                                    <option value="active" {{ $reminder->status == 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="paused" {{ $reminder->status == 'paused' ? 'selected' : '' }}>Paused</option>
                                                    <option value="completed" {{ $reminder->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                                </select>
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
                            <p class="text-sm text-[var(--color-text-secondary)] italic">No reminders set</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <p class="text-[var(--color-text-secondary)] text-center py-8">No prescriptions added yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>

