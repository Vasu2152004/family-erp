@php
    $recordTypes = [
        'general' => 'General',
        'diagnosis' => 'Diagnosis',
        'lab' => 'Lab Report',
        'imaging' => 'Imaging',
        'vaccine' => 'Vaccine',
        'allergy' => 'Allergy',
        'other' => 'Other',
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-form-field label="Family Member" labelFor="family_member_id" required>
        <select name="family_member_id" id="family_member_id" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" {{ isset($record) ? 'disabled' : 'required' }}>
            <option value="">Select member</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" @selected(old('family_member_id', $record->family_member_id ?? null) == $member->id)>
                    {{ $member->first_name }} {{ $member->last_name }}
                </option>
            @endforeach
        </select>
        @if(isset($record))
            <input type="hidden" name="family_member_id" value="{{ $record->family_member_id }}">
        @endif
        <x-error-message field="family_member_id" />
    </x-form-field>

    <x-form-field label="Record Type" labelFor="record_type" required>
        <select name="record_type" id="record_type" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" required>
            @foreach($recordTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('record_type', $record->record_type ?? 'general') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-error-message field="record_type" />
    </x-form-field>

    <x-form-field label="Title" labelFor="title" required>
        <x-input name="title" id="title" required value="{{ old('title', $record->title ?? '') }}" placeholder="e.g., Annual physical report" />
        <x-error-message field="title" />
    </x-form-field>

    <x-form-field label="Doctor (optional)" labelFor="doctor_name">
        <x-input name="doctor_name" id="doctor_name" value="{{ old('doctor_name', $record->doctor_name ?? '') }}" placeholder="Dr. Smith, General Physician" />
        <x-error-message field="doctor_name" />
    </x-form-field>

    <x-form-field label="Primary Condition" labelFor="primary_condition">
        <x-input name="primary_condition" id="primary_condition" value="{{ old('primary_condition', $record->primary_condition ?? '') }}" placeholder="e.g., Hypertension" />
        <x-error-message field="primary_condition" />
    </x-form-field>

    <x-form-field label="Severity" labelFor="severity">
        <select name="severity" id="severity" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
            <option value="">Select severity</option>
            @foreach(['mild','moderate','severe','critical'] as $severity)
                <option value="{{ $severity }}" @selected(old('severity', $record->severity ?? '') === $severity)>{{ ucfirst($severity) }}</option>
            @endforeach
        </select>
        <x-error-message field="severity" />
    </x-form-field>

    <x-form-field label="Recorded On" labelFor="recorded_at">
        <input type="date" name="recorded_at" id="recorded_at" value="{{ old('recorded_at', optional($record->recorded_at ?? null)->toDateString()) }}" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
        <x-error-message field="recorded_at" />
    </x-form-field>

    <x-form-field label="Follow-up Date" labelFor="follow_up_at">
        <input type="date" name="follow_up_at" id="follow_up_at" value="{{ old('follow_up_at', optional($record->follow_up_at ?? null)->toDateString()) }}" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
        <x-error-message field="follow_up_at" />
    </x-form-field>

    <x-form-field label="Symptoms" labelFor="symptoms" class="md:col-span-2">
        <textarea name="symptoms" id="symptoms" rows="2" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="List reported symptoms">{{ old('symptoms', $record->symptoms ?? '') }}</textarea>
        <x-error-message field="symptoms" />
    </x-form-field>

    <x-form-field label="Diagnosis" labelFor="diagnosis" class="md:col-span-2">
        <textarea name="diagnosis" id="diagnosis" rows="2" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Provisional or final diagnosis">{{ old('diagnosis', $record->diagnosis ?? '') }}</textarea>
        <x-error-message field="diagnosis" />
    </x-form-field>

    <x-form-field label="Treatment Plan" labelFor="treatment_plan" class="md:col-span-2">
        <textarea name="treatment_plan" id="treatment_plan" rows="3" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Medications, lifestyle, referrals">{{ old('treatment_plan', $record->treatment_plan ?? '') }}</textarea>
        <x-error-message field="treatment_plan" />
    </x-form-field>

    <x-form-field label="Summary" labelFor="summary">
        <textarea name="summary" id="summary" rows="3" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Key findings or diagnosis">{{ old('summary', $record->summary ?? '') }}</textarea>
        <x-error-message field="summary" />
    </x-form-field>

    <x-form-field label="Notes" labelFor="notes" class="md:col-span-2">
        <textarea name="notes" id="notes" rows="4" class="w-full rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-secondary)] px-4 py-2.5 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Treatment plan, follow-ups, labs">{{ old('notes', $record->notes ?? '') }}</textarea>
        <x-error-message field="notes" />
    </x-form-field>
</div>

