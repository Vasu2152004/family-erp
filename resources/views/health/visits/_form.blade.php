@php
    $statuses = ['scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-form-field label="Family Member" labelFor="family_member_id" required>
        <select name="family_member_id" id="family_member_id" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" {{ isset($visit) ? 'disabled' : 'required' }}>
            <option value="">Select member</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" @selected(old('family_member_id', $visit->family_member_id ?? null) == $member->id)>
                    {{ $member->first_name }} {{ $member->last_name }}
                </option>
            @endforeach
        </select>
        @if(isset($visit))
            <input type="hidden" name="family_member_id" value="{{ $visit->family_member_id }}">
        @endif
        <x-error-message field="family_member_id" />
    </x-form-field>

    <x-form-field label="Link to Medical Record (optional)" labelFor="medical_record_id">
        <select name="medical_record_id" id="medical_record_id" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">None</option>
            @foreach($records as $record)
                <option value="{{ $record->id }}" @selected(old('medical_record_id', $visit->medical_record_id ?? null) == $record->id)>
                    {{ $record->title }} ({{ $record->family_member?->first_name ?? '' }})
                </option>
            @endforeach
        </select>
        <x-error-message field="medical_record_id" />
    </x-form-field>

    <x-form-field label="Doctor Name" labelFor="doctor_name">
        <x-input name="doctor_name" id="doctor_name" value="{{ old('doctor_name', $visit->doctor_name ?? '') }}" placeholder="Dr. Smith" />
        <x-error-message field="doctor_name" />
    </x-form-field>

    <x-form-field label="Specialization" labelFor="specialization">
        <x-input name="specialization" id="specialization" value="{{ old('specialization', $visit->specialization ?? '') }}" placeholder="Cardiologist" />
        <x-error-message field="specialization" />
    </x-form-field>

    <x-form-field label="Clinic / Hospital" labelFor="clinic">
        <x-input name="clinic" id="clinic" value="{{ old('clinic', $visit->clinic ?? '') }}" placeholder="City Hospital" />
        <x-error-message field="clinic" />
    </x-form-field>

    <x-form-field label="Status" labelFor="status" required>
        <select name="status" id="status" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" required>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $visit->status ?? 'scheduled') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-error-message field="status" />
    </x-form-field>

    <x-form-field label="Visit Date" labelFor="visit_date" required>
        <input type="date" name="visit_date" id="visit_date" value="{{ old('visit_date', optional($visit->visit_date ?? null)->toDateString()) }}" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" required />
        <x-error-message field="visit_date" />
    </x-form-field>

    <x-form-field label="Follow-up Date" labelFor="follow_up_at">
        <input type="date" name="follow_up_at" id="follow_up_at" value="{{ old('follow_up_at', optional($visit->follow_up_at ?? null)->toDateString()) }}" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
        <x-error-message field="follow_up_at" />
    </x-form-field>

    <x-form-field label="Reason" labelFor="reason">
        <x-input name="reason" id="reason" value="{{ old('reason', $visit->reason ?? '') }}" placeholder="e.g., Annual check-up, fever" />
        <x-error-message field="reason" />
    </x-form-field>

    <x-form-field label="Diagnosis" labelFor="diagnosis">
        <x-input name="diagnosis" id="diagnosis" value="{{ old('diagnosis', $visit->diagnosis ?? '') }}" placeholder="e.g., Migraine" />
        <x-error-message field="diagnosis" />
    </x-form-field>

    <x-form-field label="Notes" labelFor="notes" class="md:col-span-2">
        <textarea name="notes" id="notes" rows="4" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Treatment plan, tests ordered, observations">{{ old('notes', $visit->notes ?? '') }}</textarea>
        <x-error-message field="notes" />
    </x-form-field>
</div>

