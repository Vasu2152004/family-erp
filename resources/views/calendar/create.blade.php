<x-app-layout title="Add Event: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Calendar', 'url' => route('families.calendar.index', ['family' => $family->id])],
            ['label' => 'Add Event'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Create Event</h1>
            </div>

            <x-form method="POST" action="{{ route('families.calendar.store', ['family' => $family->id]) }}" class="space-y-4">
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                <div>
                    <x-label for="title" required>Title</x-label>
                    <x-input type="text" id="title" name="title" value="{{ old('title') }}" class="mt-1 w-full" required />
                    <x-error-message field="title" />
                </div>

                <div>
                    <x-label for="description">Description</x-label>
                    <x-textarea id="description" name="description" rows="3" value="{{ old('description') }}" class="mt-1 w-full" />
                    <x-error-message field="description" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="start_at" required>Start (IST)</x-label>
                        <x-input type="datetime-local" id="start_at" name="start_at" value="{{ old('start_at') }}" class="mt-1 w-full" required />
                        <x-error-message field="start_at" />
                    </div>
                    <div>
                        <x-label for="end_at">End (IST)</x-label>
                        <x-input type="datetime-local" id="end_at" name="end_at" value="{{ old('end_at') }}" class="mt-1 w-full" />
                        <x-error-message field="end_at" />
                    </div>
                </div>

                <div>
                    <x-label for="reminder_before_minutes">Reminder</x-label>
                    <x-select id="reminder_before_minutes" name="reminder_before_minutes" class="mt-1 w-full">
                        <option value="">No reminder</option>
                        @foreach([5, 10, 15, 30, 60, 120, 1440] as $minutes)
                            <option value="{{ $minutes }}" {{ old('reminder_before_minutes') == $minutes ? 'selected' : '' }}>
                                {{ $minutes >= 60 ? $minutes / 60 . ' hour(s)' : $minutes . ' minutes' }} before
                            </option>
                        @endforeach
                    </x-select>
                    <x-error-message field="reminder_before_minutes" />
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit" variant="primary" size="md">Save Event</x-button>
                    <a href="{{ route('families.calendar.index', ['family' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)] text-sm">Cancel</a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>

