<x-app-layout title="Edit Doctor Visit">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', ['family' => $family->id])],
            ['label' => 'Doctor Visits', 'url' => route('families.health.visits.index', ['family' => $family->id])],
            ['label' => 'Edit Visit'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Edit Doctor Visit</h2>

            <x-form method="POST" action="{{ route('families.health.visits.update', ['family' => $family->id, 'visit' => $visit->id]) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-label for="family_member_id" required>Family Member</x-label>
                        <select name="family_member_id" id="family_member_id" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">Select member</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" {{ old('family_member_id', $visit->family_member_id) == $member->id ? 'selected' : '' }}>{{ $member->first_name }} {{ $member->last_name }}</option>
                            @endforeach
                        </select>
                        <x-error-message field="family_member_id" />
                    </div>

                    <div>
                        <x-label for="status">Status</x-label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="scheduled" {{ old('status', $visit->status ?? 'scheduled') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="completed" {{ old('status', $visit->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status', $visit->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <x-error-message field="status" />
                    </div>

                    <div>
                        <x-label for="medical_record_id">Medical Record</x-label>
                        <select name="medical_record_id" id="medical_record_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">Select record (optional)</option>
                            @foreach($medicalRecords as $record)
                                @php
                                    $displayParts = [];
                                    $displayParts[] = $record->title;
                                    if ($record->primary_condition) {
                                        $displayParts[] = $record->primary_condition;
                                    }
                                    if ($record->familyMember) {
                                        $displayParts[] = $record->familyMember->first_name . ' ' . $record->familyMember->last_name;
                                    }
                                    $displayParts[] = ucfirst($record->record_type);
                                    $displayParts[] = $record->created_at->format('M d, Y');
                                    $displayText = implode(' • ', $displayParts);
                                @endphp
                                <option value="{{ $record->id }}" {{ old('medical_record_id', $visit->medical_record_id) == $record->id ? 'selected' : '' }}>
                                    {{ $displayText }}
                                </option>
                            @endforeach
                        </select>
                        <x-error-message field="medical_record_id" />
                    </div>

                    <div>
                        <x-label for="visit_date" required>Visit Date</x-label>
                        <x-input type="date" name="visit_date" id="visit_date" value="{{ old('visit_date', $visit->visit_date->format('Y-m-d')) }}" required class="mt-1" />
                        <x-error-message field="visit_date" />
                    </div>

                    <div>
                        <x-label for="visit_time">Visit Time</x-label>
                        <x-input type="time" name="visit_time" id="visit_time" value="{{ old('visit_time', $visit->visit_time ? \Carbon\Carbon::parse($visit->visit_time)->format('H:i') : '') }}" class="mt-1" />
                        <x-error-message field="visit_time" />
                    </div>

                    <div>
                        <x-label for="doctor_name" required>Doctor Name</x-label>
                        <x-input type="text" name="doctor_name" id="doctor_name" value="{{ old('doctor_name', $visit->doctor_name) }}" required class="mt-1" />
                        <x-error-message field="doctor_name" />
                    </div>

                    <div>
                        <x-label for="clinic_name">Clinic/Hospital Name</x-label>
                        <x-input type="text" name="clinic_name" id="clinic_name" value="{{ old('clinic_name', $visit->clinic_name) }}" class="mt-1" />
                        <x-error-message field="clinic_name" />
                    </div>

                    <div>
                        <x-label for="specialization">Specialization</x-label>
                        <x-input type="text" name="specialization" id="specialization" value="{{ old('specialization', $visit->specialization) }}" class="mt-1" />
                        <x-error-message field="specialization" />
                    </div>

                    <div>
                        <x-label for="visit_type" required>Visit Type</x-label>
                        <select name="visit_type" id="visit_type" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="consultation" {{ old('visit_type', $visit->visit_type ?? 'consultation') == 'consultation' ? 'selected' : '' }}>Consultation</option>
                            <option value="follow_up" {{ old('visit_type', $visit->visit_type) == 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                            <option value="emergency" {{ old('visit_type', $visit->visit_type) == 'emergency' ? 'selected' : '' }}>Emergency</option>
                            <option value="routine_checkup" {{ old('visit_type', $visit->visit_type) == 'routine_checkup' ? 'selected' : '' }}>Routine Checkup</option>
                            <option value="surgery" {{ old('visit_type', $visit->visit_type) == 'surgery' ? 'selected' : '' }}>Surgery</option>
                            <option value="other" {{ old('visit_type', $visit->visit_type) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <x-error-message field="visit_type" />
                    </div>

                    <div>
                        <x-label for="next_visit_date">Next Visit Date</x-label>
                        <x-input type="date" name="next_visit_date" id="next_visit_date" value="{{ old('next_visit_date', $visit->next_visit_date?->format('Y-m-d')) }}" class="mt-1" />
                        <x-error-message field="next_visit_date" />
                    </div>
                </div>

                    <div>
                        <x-label for="chief_complaint">Chief Complaint</x-label>
                        <x-input type="text" name="chief_complaint" id="chief_complaint" value="{{ old('chief_complaint', $visit->chief_complaint) }}" class="mt-1" />
                        <x-error-message field="chief_complaint" />
                    </div>

                <div>
                    <x-label for="examination_findings">Examination Findings</x-label>
                    <textarea name="examination_findings" id="examination_findings" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('examination_findings', $visit->examination_findings) }}</textarea>
                    <x-error-message field="examination_findings" />
                </div>

                    <div>
                        <x-label for="diagnosis">Diagnosis</x-label>
                        <x-input type="text" name="diagnosis" id="diagnosis" value="{{ old('diagnosis', $visit->diagnosis) }}" class="mt-1" />
                        <x-error-message field="diagnosis" />
                    </div>

                <div>
                    <x-label for="treatment_given">Treatment Given</x-label>
                    <textarea name="treatment_given" id="treatment_given" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('treatment_given', $visit->treatment_given) }}</textarea>
                    <x-error-message field="treatment_given" />
                </div>

                <div>
                    <x-label for="notes">Notes</x-label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('notes', $visit->notes) }}</textarea>
                    <x-error-message field="notes" />
                </div>

                <div class="flex gap-4">
                    <x-button type="submit" variant="primary" size="md">Update Visit</x-button>
                    <a href="{{ route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id]) }}">
                        <x-button type="button" variant="outline" size="md">Cancel</x-button>
                    </a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>

