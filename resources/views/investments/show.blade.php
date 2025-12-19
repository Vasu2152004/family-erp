<x-app-layout title="Investment: {{ $investment->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Investments', 'url' => route('investments.index', ['family_id' => $family->id])],
            ['label' => $investment->name]
        ]" />

        <!-- Investment Details -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $investment->name }}</h1>
                        @if($investment->is_hidden)
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Hidden</span>
                        @endif
                    </div>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        {{ str_replace('_', ' ', $investment->investment_type) }}
                        @if($investment->owner_name)
                            • Owner: {{ $investment->owner_name }}
                        @endif
                        @if($investment->createdBy)
                            • Created by: {{ $investment->createdBy->name }}
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    @can('update', $investment)
                        <a href="{{ route('investments.edit', ['investment' => $investment->id, 'family_id' => $family->id]) }}">
                            <x-button variant="outline" size="md">Edit</x-button>
                        </a>
                    @endcan
                </div>
            </div>

            @if($investment->is_hidden && !$isUnlocked)
                <!-- Hidden Investment - Show Unlock Options -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-2">Hidden Investment</h3>
                    <p class="text-sm text-yellow-700 mb-4">
                        This investment is hidden and requires authentication to view details.
                    </p>

                    @php
                        $role = Auth::user()?->getFamilyRole($investment->family_id);
                        $isAdminOrOwner = $role && in_array($role->role, ['OWNER', 'ADMIN']);
                    @endphp
                    @if($isOwner)
                        <!-- Owner can unlock with PIN -->
                        <form method="POST" action="{{ route('investments.unlock', ['investment' => $investment->id, 'family_id' => $family->id]) }}" class="space-y-4">
                            @csrf
                            <div>
                                <x-label for="pin" required>Enter PIN to Unlock</x-label>
                                <x-input
                                    type="password"
                                    name="pin"
                                    id="pin"
                                    required
                                    minlength="4"
                                    maxlength="20"
                                    placeholder="Enter your PIN"
                                    class="mt-1"
                                />
                                @error('pin')
                                    <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-button type="submit" variant="primary" size="md">Unlock Investment</x-button>
                        </form>
                    @elseif($canRequestUnlock && $isAdminOrOwner)
                        <!-- Admin can request unlock if owner is deceased -->
                        <div class="space-y-4">
                            @if($unlockRequests->count() > 0)
                                @foreach($unlockRequests as $request)
                                    <div class="bg-white rounded-lg p-4 border border-yellow-200">
                                        <p class="text-sm text-yellow-800">
                                            <strong>Request Status:</strong> 
                                            @if($request->status === 'pending')
                                                Pending (Request #{{ $request->request_count }} of 3)
                                                @if($request->request_count >= 3)
                                                    <span class="ml-2 text-xs font-semibold">Eligible for auto-unlock</span>
                                                @endif
                                            @elseif($request->status === 'approved')
                                                Approved - Investment is now accessible
                                            @elseif($request->status === 'auto_unlocked')
                                                Auto-unlocked after 3 requests
                                            @else
                                                Rejected
                                            @endif
                                        </p>
                                        @if($request->status === 'pending' && $request->canRequestAgain())
                                            <form method="POST" action="{{ route('investments.request-unlock', ['investment' => $investment->id, 'family_id' => $family->id]) }}" class="mt-2">
                                                @csrf
                                                <x-button type="submit" variant="outline" size="sm">Request Again</x-button>
                                            </form>
                                        @elseif($request->status === 'pending')
                                            <p class="text-xs text-yellow-600 mt-2">
                                                You can request again in {{ $request->getDaysUntilNextRequest() }} day(s).
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            @if($unlockRequests->where('status', 'pending')->count() === 0)
                                <form method="POST" action="{{ route('investments.request-unlock', ['investment' => $investment->id, 'family_id' => $family->id]) }}">
                                    @csrf
                                    <x-button type="submit" variant="primary" size="md" :disabled="$cooldownActive">
                                        @if($cooldownActive)
                                            Cooldown ({{ $cooldownDays }} day(s) left)
                                        @else
                                            Request Unlock
                                        @endif
                                    </x-button>
                                    @if($cooldownActive)
                                        <p class="text-xs text-yellow-600 mt-2">
                                            You can request again in {{ $cooldownDays }} day(s).
                                        </p>
                                    @else
                                        <p class="text-xs text-yellow-600 mt-2">
                                            Investment owner is deceased. After 3 requests with no response, it will be automatically unlocked.
                                        </p>
                                    @endif
                                </form>
                            @endif
                        </div>
                    @else
                        <!-- Admin can unlock with PIN -->
                        <form method="POST" action="{{ route('investments.unlock', ['investment' => $investment->id, 'family_id' => $family->id]) }}" class="space-y-4">
                            @csrf
                            <div>
                                <x-label for="pin" required>Enter PIN to Unlock</x-label>
                                <x-input
                                    type="password"
                                    name="pin"
                                    id="pin"
                                    required
                                    minlength="4"
                                    maxlength="20"
                                    placeholder="Enter PIN"
                                    class="mt-1"
                                />
                                @error('pin')
                                    <p class="mt-1 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-button type="submit" variant="primary" size="md">Unlock Investment</x-button>
                        </form>
                    @endif
                </div>
            @else
                <!-- Visible Investment Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                        <p class="text-sm text-[var(--color-text-secondary)]">Investment Amount</p>
                        <p class="text-2xl font-bold text-[var(--color-text-primary)]">₹{{ number_format($investment->amount, 2) }}</p>
                    </div>
                    @if($investment->current_value)
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                            <p class="text-sm text-[var(--color-text-secondary)]">Current Value</p>
                            <p class="text-2xl font-bold text-[var(--color-text-primary)]">₹{{ number_format($investment->current_value, 2) }}</p>
                        </div>
                    @endif
                </div>

                @if($investment->description)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">Description</h3>
                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $investment->description }}</p>
                    </div>
                @endif

                @if($investment->details)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">Investment Details</h3>
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                            <pre class="text-sm text-[var(--color-text-secondary)] whitespace-pre-wrap">{{ $investment->details }}</pre>
                        </div>
                    </div>
                @endif

                @if($investment->is_hidden && $isUnlocked)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <p class="text-sm text-green-800">
                            <strong>Unlocked:</strong> This investment has been unlocked for you. You now have permanent access to view its details.
                        </p>
                    </div>
                @endif
            @endif

            <div class="flex gap-2 pt-4 border-t border-[var(--color-border-primary)]">
                <a href="{{ route('investments.index', ['family_id' => $family->id]) }}">
                    <x-button variant="outline" size="md">Back to List</x-button>
                </a>
                @can('update', $investment)
                    <a href="{{ route('investments.edit', ['investment' => $investment->id, 'family_id' => $family->id]) }}">
                        <x-button variant="primary" size="md">Edit</x-button>
                    </a>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>



