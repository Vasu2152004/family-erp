<x-app-layout title="Asset: {{ $asset->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Assets', 'url' => route('assets.index', ['family_id' => $family->id])],
            ['label' => $asset->name]
        ]" />

        <!-- Asset Details -->
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">{{ $asset->name }}</h1>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-2">
                        {{ str_replace('_', ' ', $asset->asset_type) }}
                        @if($asset->owner_name)
                            • Owner: {{ $asset->owner_name }}
                        @endif
                        @if($asset->createdBy)
                            • Created by: {{ $asset->createdBy->name }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    @if($asset->is_locked)
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Locked</span>
                    @else
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Unlocked</span>
                    @endif
                    <div class="flex gap-2">
                    @can('update', $asset)
                        <a href="{{ route('assets.edit', ['asset' => $asset->id, 'family_id' => $family->id]) }}">
                            <x-button variant="outline" size="md">Edit</x-button>
                        </a>
                    @endcan
                    </div>
                </div>
            </div>

            @if($asset->is_locked && !$isUnlocked)
                <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)] mb-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-sm text-[var(--color-text-secondary)] font-semibold">Locked Asset</p>
                            <p class="text-lg font-bold text-[var(--color-text-primary)]">Enter PIN to view details.</p>
                            @if($asset->isOwnerDeceased())
                                <p class="text-xs text-[var(--color-text-secondary)]">
                                    Owner is marked deceased. You can request unlock. Auto-unlock after 3 requests if no admin approval.
                                    @if(isset($pendingRequest) && $pendingRequest)
                                        <br>Current request count: {{ $pendingRequest->request_count }} of 3 (last: {{ $pendingRequest->last_requested_at?->diffForHumans() }})
                                    @endif
                                    @if(isset($pendingTotal))
                                        <br>Pending requests overall: {{ $pendingTotal }}
                                    @endif
                                </p>
                            @endif
                        </div>
                        @can('view', $asset)
                            <x-form method="POST" action="{{ route('assets.unlock', ['asset' => $asset->id, 'family_id' => $family->id]) }}" class="flex flex-col md:flex-row md:items-end gap-3 w-full md:w-auto">
                                @csrf
                                <input type="hidden" name="family_id" value="{{ $family->id }}">
                                <div class="w-full md:w-64">
                                    <x-label for="unlock_pin" required>PIN</x-label>
                                    <x-input
                                        type="password"
                                        name="pin"
                                        id="unlock_pin"
                                        minlength="4"
                                        maxlength="20"
                                        required
                                        class="mt-1"
                                    />
                                </div>
                                <x-button type="submit" variant="primary" size="md">Unlock (session)</x-button>
                            </x-form>
                            @if($canRequestUnlock)
                                <x-form method="POST" action="{{ route('assets.request-unlock', ['asset' => $asset->id, 'family_id' => $family->id]) }}" class="flex flex-col md:flex-row md:items-end gap-3 w-full md:w-auto">
                                    @csrf
                                    <input type="hidden" name="family_id" value="{{ $family->id }}">
                                    <x-button type="submit" variant="outline" size="md" :disabled="$cooldownActive">
                                        @if($cooldownActive)
                                            Cooldown ({{ $cooldownDays }} day(s) left)
                                        @elseif(isset($pendingRequest) && $pendingRequest)
                                            Request Again ({{ $pendingRequest->request_count }} of 3)
                                        @else
                                            Request Unlock (owner deceased)
                                        @endif
                                    </x-button>
                                </x-form>
                                @if($cooldownActive)
                                    <p class="text-xs text-[var(--color-text-secondary)] mt-1">
                                        You can request again in {{ $cooldownDays }} day(s).
                                    </p>
                                @endif
                            @endif
                        @else
                            <p class="text-sm text-[var(--color-text-secondary)]">You do not have permission to unlock this asset.</p>
                        @endcan
                    </div>
                </div>
            @else
                @can('update', $asset)
                    <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)] mb-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                            <div>
                                <p class="text-sm text-[var(--color-text-secondary)] font-semibold">Lock Status</p>
                                <p class="text-lg font-bold text-[var(--color-text-primary)]">Unlocked</p>
                            </div>
                            <x-form method="POST" action="{{ route('assets.toggle-lock', ['asset' => $asset->id, 'family_id' => $family->id]) }}" class="flex flex-col md:flex-row md:items-end gap-3 w-full md:w-auto">
                                @csrf
                                <input type="hidden" name="family_id" value="{{ $family->id }}">
                                <input type="hidden" name="is_locked" value="1">
                                <div class="w-full md:w-64">
                                    <x-label for="lock_pin" required>Set PIN to Lock</x-label>
                                    <x-input
                                        type="password"
                                        name="pin"
                                        id="lock_pin"
                                        minlength="4"
                                        maxlength="20"
                                        required
                                        class="mt-1"
                                    />
                                </div>
                                <x-button type="submit" variant="outline" size="md">Lock Asset</x-button>
                            </x-form>
                        </div>
                    </div>
                @endcan

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    @if($asset->purchase_value)
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                            <p class="text-sm text-[var(--color-text-secondary)]">Purchase Value</p>
                            <p class="text-2xl font-bold text-[var(--color-text-primary)]">₹{{ number_format($asset->purchase_value, 2) }}</p>
                        </div>
                    @endif
                    @if($asset->current_value)
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                            <p class="text-sm text-[var(--color-text-secondary)]">Current Value</p>
                            <p class="text-2xl font-bold text-[var(--color-text-primary)]">₹{{ number_format($asset->current_value, 2) }}</p>
                        </div>
                    @endif
                </div>

                @if($asset->description)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">Description</h3>
                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $asset->description }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    @if($asset->purchase_date)
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--color-text-secondary)] mb-1">Purchase Date</h3>
                            <p class="text-sm text-[var(--color-text-primary)]">{{ $asset->purchase_date->format('F d, Y') }}</p>
                        </div>
                    @endif
                    @if($asset->location)
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--color-text-secondary)] mb-1">Location</h3>
                            <p class="text-sm text-[var(--color-text-primary)]">{{ $asset->location }}</p>
                        </div>
                    @endif
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">Notes</h3>
                    <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4 border border-[var(--color-border-primary)]">
                        @if(!empty($asset->notes))
                            <pre class="text-sm text-[var(--color-text-secondary)] whitespace-pre-wrap">{{ $asset->notes }}</pre>
                        @else
                            <p class="text-sm text-[var(--color-text-secondary)]">No notes available for this asset.</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex gap-2 pt-4 border-t border-[var(--color-border-primary)]">
                <a href="{{ route('assets.index', ['family_id' => $family->id]) }}">
                    <x-button variant="outline" size="md">Back to List</x-button>
                </a>
                @can('update', $asset)
                    <a href="{{ route('assets.edit', ['asset' => $asset->id, 'family_id' => $family->id]) }}">
                        <x-button variant="primary" size="md">Edit</x-button>
                    </a>
                @endcan
                @can('delete', $asset)
                    <x-form 
                        method="POST" 
                        action="{{ route('assets.destroy', ['asset' => $asset->id, 'family_id' => $family->id]) }}" 
                        class="inline"
                        data-confirm="Delete this asset?"
                        data-confirm-title="Delete Asset"
                        data-confirm-variant="danger"
                    >
                        @csrf
                        @method('DELETE')
                        <x-button variant="danger" size="md">Delete</x-button>
                    </x-form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>











