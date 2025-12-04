<x-app-layout title="Family Member Requests">
    <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Family Member Requests</h1>
            <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                Review requests to join your family groups.
            </p>
        </div>

        @if($pendingRequests->count() > 0)
            <div class="space-y-6">
                @foreach($pendingRequests as $request)
                    <div class="bg-[var(--color-bg-secondary)] rounded-lg border border-[var(--color-border-primary)] p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-[var(--color-text-primary)]">
                                Request for {{ $request->first_name }} {{ $request->last_name }}
                            </h3>
                            <span class="text-sm text-[var(--color-text-secondary)]">
                                Requested by {{ $request->requestedBy->name }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-[var(--color-text-secondary)] mb-6">
                            <div>
                                <p><span class="font-medium">Family:</span> {{ $request->family->name }}</p>
                                <p><span class="font-medium">Relation:</span> {{ $request->relation }}</p>
                                <p><span class="font-medium">Gender:</span> {{ ucfirst($request->gender) }}</p>
                            </div>
                            <div>
                                @if($request->date_of_birth)
                                    <p><span class="font-medium">Date of Birth:</span> {{ $request->date_of_birth->format('F d, Y') }}</p>
                                @endif
                                @if($request->email)
                                    <p><span class="font-medium">Email:</span> {{ $request->email }}</p>
                                @endif
                                @if($request->phone)
                                    <p><span class="font-medium">Phone:</span> {{ $request->phone }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <form action="{{ route('family-member-requests.accept', $request) }}" method="POST">
                                @csrf
                                <x-button type="submit" variant="primary" size="sm">Accept</x-button>
                            </form>
                            <form action="{{ route('family-member-requests.reject', $request) }}" method="POST">
                                @csrf
                                <x-button type="submit" variant="outline" size="sm">Reject</x-button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $pendingRequests->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-[var(--color-text-secondary)] mb-4">No pending family member requests.</p>
                <p class="text-[var(--color-text-secondary)]">You're all caught up!</p>
            </div>
        @endif
    </div>
</x-app-layout>

