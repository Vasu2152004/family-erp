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

            <form method="POST" action="{{ route('families.calendar.store', ['family' => $family->id]) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="family_id" value="{{ $family->id }}">

                <div>
                    <x-label for="title" required>Title</x-label>
                    <x-input type="text" id="title" name="title" value="{{ old('title') }}" class="mt-1 w-full" required />
                    @error('title') <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-label for="description">Description</x-label>
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="start_at" required>Start</x-label>
                        <x-input type="datetime-local" id="start_at" name="start_at" value="{{ old('start_at') }}" class="mt-1 w-full" required />
                        @error('start_at') <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-label for="end_at">End</x-label>
                        <x-input type="datetime-local" id="end_at" name="end_at" value="{{ old('end_at') }}" class="mt-1 w-full" />
                        @error('end_at') <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <x-label for="reminder_before_minutes">Reminder</x-label>
                    <select id="reminder_before_minutes" name="reminder_before_minutes" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">No reminder</option>
                        @foreach([5, 10, 15, 30, 60, 120, 1440] as $minutes)
                            <option value="{{ $minutes }}" {{ old('reminder_before_minutes') == $minutes ? 'selected' : '' }}>
                                {{ $minutes >= 60 ? $minutes / 60 . ' hour(s)' : $minutes . ' minutes' }} before
                            </option>
                        @endforeach
                    </select>
                    @error('reminder_before_minutes') <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit" variant="primary" size="md">Save Event</x-button>
                    <a href="{{ route('families.calendar.index', ['family' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)] text-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

