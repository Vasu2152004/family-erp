<x-app-layout title="Visit Details">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', $family)],
            ['label' => 'Doctor Visits', 'url' => route('families.health.visits.index', $family)],
            ['label' => 'Details'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-[var(--color-text-secondary)]">Visit</p>
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">
                        {{ $visit->doctor_name ?? 'Doctor visit' }}
                        <span class="text-sm font-medium text-[var(--color-text-secondary)]">• {{ $visit->visit_date?->format('M d, Y') }}</span>
                    </h2>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-1">
                        {{ $visit->familyMember?->first_name }} {{ $visit->familyMember?->last_name }}
                        @if($visit->clinic) • {{ $visit->clinic }} @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('families.health.visits.edit', [$family, $visit]) }}" class="px-3 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] text-sm hover:bg-[var(--color-bg-secondary)]">Edit</a>
                    <form method="POST" action="{{ route('families.health.visits.destroy', [$family, $visit]) }}" onsubmit="return confirm('Delete this visit?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 rounded-lg border border-red-200 text-red-600 text-sm hover:bg-red-50">Delete</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="p-4 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                    <p class="text-xs text-[var(--color-text-secondary)] mb-1">Status</p>
                    <p class="font-semibold text-[var(--color-text-primary)] capitalize">{{ $visit->status }}</p>
                </div>
                <div class="p-4 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                    <p class="text-xs text-[var(--color-text-secondary)] mb-1">Reason</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $visit->reason ?? '—' }}</p>
                </div>
                <div class="p-4 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                    <p class="text-xs text-[var(--color-text-secondary)] mb-1">Diagnosis</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $visit->diagnosis ?? 'Pending' }}</p>
                </div>
                <div class="p-4 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                    <p class="text-xs text-[var(--color-text-secondary)] mb-1">Follow-up</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $visit->follow_up_at?->format('M d, Y') ?? 'None' }}</p>
                </div>
                @if($visit->notes)
                    <div class="md:col-span-2 p-4 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)]">
                        <p class="text-xs text-[var(--color-text-secondary)] mb-1">Notes</p>
                        <p class="text-sm text-[var(--color-text-primary)] whitespace-pre-line">{{ $visit->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Prescriptions</h3>
                        <p class="text-sm text-[var(--color-text-secondary)]">Medicines prescribed during this visit.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('families.health.visits.prescriptions.store', [$family, $visit]) }}" class="space-y-4 mb-6" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="doctor_visit_id" value="{{ $visit->id }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form-field label="Medication" labelFor="medication_name" required>
                            <x-input name="medication_name" id="medication_name" required placeholder="e.g., Amoxicillin" />
                            <x-error-message field="medication_name" />
                        </x-form-field>
                        <x-form-field label="Dosage" labelFor="dosage">
                            <x-input name="dosage" id="dosage" placeholder="1 tablet" />
                            <x-error-message field="dosage" />
                        </x-form-field>
                        <x-form-field label="Frequency" labelFor="frequency">
                            <x-input name="frequency" id="frequency" placeholder="Twice daily after meals" />
                            <x-error-message field="frequency" />
                        </x-form-field>
                        <x-form-field label="Status" labelFor="status">
                            <select name="status" id="status" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)]">
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <x-error-message field="status" />
                        </x-form-field>
                        <x-form-field label="Start Date" labelFor="start_date">
                            <input type="date" name="start_date" id="start_date" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)]" />
                            <x-error-message field="start_date" />
                        </x-form-field>
                        <x-form-field label="End Date" labelFor="end_date">
                            <input type="date" name="end_date" id="end_date" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)]" />
                            <x-error-message field="end_date" />
                        </x-form-field>
                        <x-form-field label="Instructions" labelFor="instructions" class="md:col-span-2">
                            <textarea name="instructions" id="instructions" rows="2" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)]" placeholder="Take after meals, avoid driving"></textarea>
                            <x-error-message field="instructions" />
                        </x-form-field>
                        <x-form-field label="Attachment (PDF/PNG)" labelFor="attachment" class="md:col-span-2">
                            <input type="file" name="attachment" id="attachment" accept=".pdf,.png" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-3 py-2 text-[var(--color-text-primary)]">
                            <x-error-message field="attachment" />
                        </x-form-field>
                    </div>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="primary" size="sm">Add Prescription</x-button>
                    </div>
                </form>

                <div class="space-y-4">
                    @forelse($visit->prescriptions as $prescription)
                        <div class="p-4 rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)]">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-semibold text-[var(--color-text-primary)]">{{ $prescription->medication_name }}</h4>
                                    <p class="text-xs text-[var(--color-text-secondary)]">
                                        {{ $prescription->dosage }} • {{ $prescription->frequency ?? 'As directed' }}
                                    </p>
                                    @if($prescription->file_path)
                                        <div class="flex items-center gap-2 text-xs">
                                            <a href="{{ Storage::url($prescription->file_path) }}" class="text-[var(--color-primary)] hover:underline" target="_blank" rel="noopener">View</a>
                                            <a href="{{ route('families.health.visits.prescriptions.download', [$family, $visit, $prescription]) }}" class="text-[var(--color-primary)] hover:underline">Download</a>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 capitalize">{{ $prescription->status }}</span>
                                    <form method="POST" action="{{ route('families.health.visits.prescriptions.destroy', [$family, $visit, $prescription]) }}" onsubmit="return confirm('Remove this prescription?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </div>
                            @if($prescription->instructions)
                                <p class="text-sm text-[var(--color-text-secondary)] mt-2">{{ $prescription->instructions }}</p>
                            @endif
                            <div class="mt-3">
                                <details class="rounded border border-[var(--color-border-primary)] bg-white/40">
                                    <summary class="px-3 py-2 cursor-pointer text-sm font-semibold text-[var(--color-text-primary)]">Edit prescription</summary>
                                    <form method="POST" action="{{ route('families.health.visits.prescriptions.update', [$family, $visit, $prescription]) }}" class="p-3 space-y-2" enctype="multipart/form-data">
                                        @csrf
                                        @method('PATCH')
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            <x-input name="medication_name" value="{{ $prescription->medication_name }}" required />
                                            <x-input name="dosage" value="{{ $prescription->dosage }}" />
                                            <x-input name="frequency" value="{{ $prescription->frequency }}" />
                                            <select name="status" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2">
                                                <option value="active" @selected($prescription->status === 'active')>Active</option>
                                                <option value="completed" @selected($prescription->status === 'completed')>Completed</option>
                                                <option value="cancelled" @selected($prescription->status === 'cancelled')>Cancelled</option>
                                            </select>
                                            <input type="date" name="start_date" value="{{ optional($prescription->start_date)->toDateString() }}" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" />
                                            <input type="date" name="end_date" value="{{ optional($prescription->end_date)->toDateString() }}" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" />
                                            <textarea name="instructions" rows="2" class="md:col-span-2 w-full rounded border border-[var(--color-border-primary)] px-2 py-2">{{ $prescription->instructions }}</textarea>
                                            <div class="md:col-span-2 flex items-center gap-3">
                                                @if($prescription->file_path)
                                                    <a href="{{ Storage::url($prescription->file_path) }}" class="text-xs text-[var(--color-primary)] hover:underline" target="_blank" rel="noopener">Current attachment</a>
                                                @endif
                                                <input type="file" name="attachment" accept=".pdf,.png" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2">
                                            </div>
                                        </div>
                                        <div class="flex justify-end">
                                            <x-button type="submit" variant="secondary" size="sm">Update</x-button>
                                        </div>
                                    </form>
                                </details>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-sm font-semibold text-[var(--color-text-primary)]">Reminders</h5>
                                    <button type="button" class="text-xs text-[var(--color-primary)] hover:underline" onclick="document.getElementById('reminder-form-{{ $prescription->id }}').classList.toggle('hidden')">Add reminder</button>
                                </div>
                                <form id="reminder-form-{{ $prescription->id }}" class="hidden p-3 rounded border border-[var(--color-border-primary)] bg-white/60" method="POST" action="{{ route('families.health.visits.prescriptions.reminders.store', [$family, $visit, $prescription]) }}">
                                    @csrf
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                        <select name="frequency" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2">
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="once">Once</option>
                                        </select>
                                        <input type="time" name="reminder_time" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" required>
                                        <input type="date" name="start_date" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" required>
                                        <input type="date" name="end_date" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" placeholder="End date">
                                        <select name="days_of_week[]" multiple class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" size="3">
                                            <option value="mon">Mon</option>
                                            <option value="tue">Tue</option>
                                            <option value="wed">Wed</option>
                                            <option value="thu">Thu</option>
                                            <option value="fri">Fri</option>
                                            <option value="sat">Sat</option>
                                            <option value="sun">Sun</option>
                                        </select>
                                        <select name="status" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2">
                                            <option value="active">Active</option>
                                            <option value="paused">Paused</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-end mt-2">
                                        <x-button type="submit" variant="secondary" size="sm">Save Reminder</x-button>
                                    </div>
                                </form>

                                @forelse($prescription->reminders as $reminder)
                                    <div class="p-3 rounded border border-[var(--color-border-primary)] bg-white/70 flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-[var(--color-text-primary)]">
                                                {{ ucfirst($reminder->frequency) }} @ {{ $reminder->reminder_time }}
                                            </p>
                                            <p class="text-xs text-[var(--color-text-secondary)]">
                                                {{ $reminder->start_date?->format('M d, Y') }} - {{ $reminder->end_date?->format('M d, Y') ?? 'Open' }}
                                                @if($reminder->days_of_week)
                                                    • {{ implode(', ', $reminder->days_of_week) }}
                                                @endif
                                            </p>
                                            @if($reminder->next_run_at)
                                                <p class="text-xs text-[var(--color-text-secondary)]">Next: {{ $reminder->next_run_at?->format('M d, Y H:i') }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <details class="border border-[var(--color-border-primary)] rounded">
                                                <summary class="px-2 py-1 text-xs cursor-pointer">Edit</summary>
                                                <form method="POST" action="{{ route('families.health.visits.prescriptions.reminders.update', [$family, $visit, $prescription, $reminder]) }}" class="p-3 space-y-2 w-64">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="frequency" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2">
                                                        <option value="daily" @selected($reminder->frequency === 'daily')>Daily</option>
                                                        <option value="weekly" @selected($reminder->frequency === 'weekly')>Weekly</option>
                                                        <option value="once" @selected($reminder->frequency === 'once')>Once</option>
                                                    </select>
                                                    <input type="time" name="reminder_time" value="{{ $reminder->reminder_time }}" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" required>
                                                    <input type="date" name="start_date" value="{{ optional($reminder->start_date)->toDateString() }}" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" required>
                                                    <input type="date" name="end_date" value="{{ optional($reminder->end_date)->toDateString() }}" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2">
                                                    <select name="days_of_week[]" multiple class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2" size="3">
                                                        @foreach(['mon','tue','wed','thu','fri','sat','sun'] as $day)
                                                            <option value="{{ $day }}" @selected(in_array($day, $reminder->days_of_week ?? []))>{{ strtoupper($day) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="status" class="w-full rounded border border-[var(--color-border-primary)] px-2 py-2">
                                                        <option value="active" @selected($reminder->status === 'active')>Active</option>
                                                        <option value="paused" @selected($reminder->status === 'paused')>Paused</option>
                                                        <option value="completed" @selected($reminder->status === 'completed')>Completed</option>
                                                    </select>
                                                    <div class="flex justify-end">
                                                        <x-button type="submit" variant="secondary" size="sm">Update</x-button>
                                                    </div>
                                                </form>
                                            </details>
                                            <form method="POST" action="{{ route('families.health.visits.prescriptions.reminders.destroy', [$family, $visit, $prescription, $reminder]) }}" onsubmit="return confirm('Delete reminder?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-[var(--color-text-secondary)]">No reminders yet.</p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--color-text-secondary)]">No prescriptions added yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-[var(--color-bg-primary)] border border-[var(--color-border-primary)] rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">Linked Medical Record</h3>
                        <p class="text-sm text-[var(--color-text-secondary)]">Keep medical notes connected to this visit.</p>
                    </div>
                </div>
                <div class="space-y-3">
                    @if($visit->medicalRecord)
                        <div class="p-4 rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)]">
                            <h4 class="font-semibold text-[var(--color-text-primary)]">{{ $visit->medicalRecord->title }}</h4>
                            <p class="text-xs text-[var(--color-text-secondary)]">Type: {{ ucfirst($visit->medicalRecord->record_type) }}</p>
                            @if($visit->medicalRecord->summary)
                                <p class="text-sm text-[var(--color-text-secondary)] mt-2">{{ $visit->medicalRecord->summary }}</p>
                            @endif
                            <a href="{{ route('families.health.records.edit', [$family, $visit->medicalRecord]) }}" class="text-xs text-[var(--color-primary)] hover:underline mt-2 inline-block">Edit record</a>
                        </div>
                    @else
                        <p class="text-sm text-[var(--color-text-secondary)]">No record linked. Edit the visit to attach one.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

