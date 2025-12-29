<x-app-layout title="Create Medical Record">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Health', 'url' => route('families.health.index', ['family' => $family->id])],
            ['label' => 'Medical Records', 'url' => route('families.health.records.index', ['family' => $family->id])],
            ['label' => 'Create'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Create Medical Record</h2>

            <x-form method="POST" action="{{ route('families.health.records.store', ['family' => $family->id]) }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-label for="family_member_id" required>Linked Member</x-label>
                        <select name="family_member_id" id="family_member_id" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">Select member</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" {{ old('family_member_id') == $member->id ? 'selected' : '' }}>{{ $member->first_name }} {{ $member->last_name }}</option>
                            @endforeach
                        </select>
                        <x-error-message field="family_member_id" />
                    </div>

                    <div>
                        <x-label for="title" required>Title</x-label>
                        <x-input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="e.g., Annual Checkup, Blood Test Results" class="mt-1" />
                        <x-error-message field="title" />
                    </div>

                    <div>
                        <x-label for="record_type" required>Record Type</x-label>
                        <select name="record_type" id="record_type" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="general" {{ old('record_type', 'general') == 'general' ? 'selected' : '' }}>General</option>
                            <option value="diagnosis" {{ old('record_type') == 'diagnosis' ? 'selected' : '' }}>Diagnosis</option>
                            <option value="lab" {{ old('record_type') == 'lab' ? 'selected' : '' }}>Lab</option>
                            <option value="imaging" {{ old('record_type') == 'imaging' ? 'selected' : '' }}>Imaging</option>
                            <option value="vaccine" {{ old('record_type') == 'vaccine' ? 'selected' : '' }}>Vaccine</option>
                            <option value="allergy" {{ old('record_type') == 'allergy' ? 'selected' : '' }}>Allergy</option>
                            <option value="other" {{ old('record_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <x-error-message field="record_type" />
                    </div>

                    <div>
                        <x-label for="category">Category</x-label>
                        <x-input type="text" name="category" id="category" value="{{ old('category') }}" placeholder="e.g., Cardiovascular, Respiratory" class="mt-1" />
                        <x-error-message field="category" />
                    </div>

                    <div>
                        <x-label for="primary_condition">Primary Condition</x-label>
                        <x-input type="text" name="primary_condition" id="primary_condition" value="{{ old('primary_condition') }}" placeholder="e.g., Hypertension, Diabetes" class="mt-1" />
                        <x-error-message field="primary_condition" />
                    </div>

                    <div>
                        <x-label for="severity">Severity</x-label>
                        <select name="severity" id="severity" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">Select severity</option>
                            <option value="mild" {{ old('severity') == 'mild' ? 'selected' : '' }}>Mild</option>
                            <option value="moderate" {{ old('severity') == 'moderate' ? 'selected' : '' }}>Moderate</option>
                            <option value="severe" {{ old('severity') == 'severe' ? 'selected' : '' }}>Severe</option>
                            <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                        <x-error-message field="severity" />
                    </div>

                    <div>
                        <x-label for="doctor_name">Doctor Name</x-label>
                        <x-input type="text" name="doctor_name" id="doctor_name" value="{{ old('doctor_name') }}" class="mt-1" />
                        <x-error-message field="doctor_name" />
                    </div>

                    <div>
                        <x-label for="recorded_at">Recorded Date</x-label>
                        <x-input type="date" name="recorded_at" id="recorded_at" value="{{ old('recorded_at', date('Y-m-d')) }}" class="mt-1" />
                        <x-error-message field="recorded_at" />
                    </div>

                    <div>
                        <x-label for="follow_up_at">Follow-up Date</x-label>
                        <x-input type="date" name="follow_up_at" id="follow_up_at" value="{{ old('follow_up_at') }}" class="mt-1" />
                        <x-error-message field="follow_up_at" />
                    </div>
                </div>

                <div>
                    <x-label for="summary">Summary</x-label>
                    <textarea name="summary" id="summary" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('summary') }}</textarea>
                    <x-error-message field="summary" />
                </div>

                <div>
                    <x-label for="symptoms">Symptoms</x-label>
                    <textarea name="symptoms" id="symptoms" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('symptoms') }}</textarea>
                    <x-error-message field="symptoms" />
                </div>

                <div>
                    <x-label for="diagnosis">Diagnosis</x-label>
                    <textarea name="diagnosis" id="diagnosis" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('diagnosis') }}</textarea>
                    <x-error-message field="diagnosis" />
                </div>

                <div>
                    <x-label for="treatment_plan">Treatment Plan</x-label>
                    <textarea name="treatment_plan" id="treatment_plan" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('treatment_plan') }}</textarea>
                    <x-error-message field="treatment_plan" />
                </div>

                <div>
                    <x-label for="notes">Notes</x-label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('notes') }}</textarea>
                    <x-error-message field="notes" />
                </div>

                <div class="flex gap-4">
                    <x-button type="submit" variant="primary" size="md">Create Record</x-button>
                    <a href="{{ route('families.health.records.index', ['family' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">Cancel</x-button>
                    </a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>

