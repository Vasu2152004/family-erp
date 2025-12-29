<x-app-layout title="Calendar: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Calendar'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Family Calendar</h1>
                    <p class="mt-1 text-sm text-[var(--color-text-secondary)]">All members can add and edit events.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('families.calendar.create', ['family' => $family->id]) }}">
                        <x-button variant="primary" size="md">Add Event</x-button>
                    </a>
                </div>
            </div>

            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <input type="hidden" name="family_id" value="{{ $family->id }}">
                <div>
                    <x-label for="from">From</x-label>
                    <x-input type="date" id="from" name="from" value="{{ request('from') }}" class="mt-1 w-full" />
                </div>
                <div>
                    <x-label for="to">To</x-label>
                    <x-input type="date" id="to" name="to" value="{{ request('to') }}" class="mt-1 w-full" />
                </div>
                <div class="md:col-span-2 flex items-end gap-2">
                    <x-button type="submit" variant="secondary" size="md">Filter</x-button>
                    <a href="{{ route('families.calendar.index', ['family' => $family->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)] text-sm">Reset</a>
                </div>
            </x-form>

            @if($events->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase">Start</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase">End</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase">Reminder</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-[var(--color-text-secondary)] uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($events as $event)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-[var(--color-text-primary)] font-semibold">
                                        {{ $event->title }}
                                        @if($event->reminder_before_minutes)
                                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800">
                                                Reminder {{ $event->reminder_before_minutes }}m
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[var(--color-text-secondary)]">
                                        {{ $event->start_at_ist?->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[var(--color-text-secondary)]">
                                        {{ $event->end_at_ist?->format('M d, Y h:i A') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[var(--color-text-secondary)]">
                                        @if($event->reminder_before_minutes)
                                            {{ $event->reminder_before_minutes }} minutes before
                                            @if($event->reminder_sent_at)
                                                <span class="ml-1 text-xs text-green-600">(sent)</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('families.calendar.edit', ['family' => $family->id, 'event' => $event->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">Edit</a>
                                            <x-form method="POST" action="{{ route('families.calendar.destroy', ['family' => $family->id, 'event' => $event->id]) }}" onsubmit="return confirm('Delete this event?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                            </x-form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $events->withQueryString()->links() }}
                </div>
            @else
                <p class="text-[var(--color-text-secondary)]">No events yet. Add your first event.</p>
            @endif
        </div>
    </div>
</x-app-layout>

