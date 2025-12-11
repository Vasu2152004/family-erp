<x-app-layout title="Documents">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Documents'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Family Documents</h2>
                        <p class="text-sm text-[var(--color-text-secondary)]">
                            Securely store IDs, passports, insurance papers, and certificates with password protection.
                        </p>
                    </div>
                    @can('create', [\App\Models\Document::class, $family->id])
                        <a href="{{ route('families.documents.create', ['family' => $family->id]) }}">
                            <x-button variant="primary" size="md">Upload Document</x-button>
                        </a>
                    @endcan
                </div>

                <form method="GET" action="{{ route('families.documents.index', ['family' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-xl p-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title or filename" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Type</label>
                        <select name="document_type" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All types</option>
                            @foreach($documentTypes as $type)
                                <option value="{{ $type['value'] }}" @selected(($filters['document_type'] ?? '') === $type['value'])>
                                    {{ $type['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-[var(--color-text-secondary)]">Linked Member</label>
                        <select name="family_member_id" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                            <option value="">All members</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" @selected(($filters['family_member_id'] ?? '') == $member->id)>
                                    {{ $member->first_name }} {{ $member->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm text-[var(--color-text-secondary)]">Sensitivity</label>
                            <select name="sensitive" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                <option value="">All</option>
                                <option value="1" @selected(($filters['sensitive'] ?? '') === '1')>Sensitive</option>
                                <option value="0" @selected(($filters['sensitive'] ?? '') === '0')>Not sensitive</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm text-[var(--color-text-secondary)]">Expiry</label>
                            <select name="expiry" class="rounded-lg border border-[var(--color-border-primary)] bg-[var(--color-bg-primary)] px-3 py-2 text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                                <option value="">All</option>
                                <option value="with_expiry" @selected(($filters['expiry'] ?? '') === 'with_expiry')>Has expiry</option>
                                <option value="expired" @selected(($filters['expiry'] ?? '') === 'expired')>Expired</option>
                                <option value="no_expiry" @selected(($filters['expiry'] ?? '') === 'no_expiry')>No expiry</option>
                            </select>
                        </div>
                    </div>

                    <div class="md:col-span-4 flex flex-wrap gap-2 justify-end">
                        <x-button type="submit" variant="primary" size="md">Apply Filters</x-button>
                        <a href="{{ route('families.documents.index', ['family' => $family->id]) }}" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)] transition-colors">Reset</a>
                    </div>
                </form>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($documents as $document)
                    @php
                        $requiresPassword = $document->requiresPasswordFor(auth()->user());
                        $isSensitive = $document->is_sensitive;
                        $hasPassword = !empty($document->password_hash);
                        $isPasswordProtected = $document->isPasswordProtected();
                    @endphp
                    <div class="bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-[var(--color-text-secondary)] mb-1">
                                    {{ str_replace('_', ' ', strtolower($document->document_type)) }}
                                </p>
                                <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $document->title }}</h3>
                                <p class="text-sm text-[var(--color-text-secondary)]">
                                    {{ $document->original_name }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                @if($document->is_sensitive)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">Sensitive</span>
                                @endif
                                @if($isPasswordProtected)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Password required</span>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-2 text-sm text-[var(--color-text-secondary)] mb-4">
                            @if($document->familyMember)
                                <p>Linked member: <span class="font-medium text-[var(--color-text-primary)]">{{ $document->familyMember->first_name }} {{ $document->familyMember->last_name }}</span></p>
                            @endif
                            @if($document->expires_at)
                                <p>Expires: <span class="font-medium text-[var(--color-text-primary)]">{{ $document->expires_at->format('M d, Y') }}</span></p>
                            @endif
                            <p>Uploaded by: <span class="font-medium text-[var(--color-text-primary)]">{{ $document->uploader?->name }}</span></p>
                            <p>Size: <span class="font-medium text-[var(--color-text-primary)]">{{ number_format($document->file_size / 1024, 1) }} KB</span></p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="document-download-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] transition-colors"
                                data-document-id="{{ $document->id }}"
                                data-family-id="{{ $family->id }}"
                                data-requires-password="{{ $requiresPassword ? '1' : '0' }}"
                                data-is-sensitive="{{ $isSensitive ? '1' : '0' }}"
                                data-has-password="{{ $hasPassword ? '1' : '0' }}"
                                data-is-protected="{{ $isPasswordProtected ? '1' : '0' }}"
                                data-verify-url="{{ route('families.documents.verify-password', ['family' => $family->id, 'document' => $document]) }}"
                                data-download-url="{{ route('families.documents.download', ['family' => $family->id, 'document' => $document]) }}"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"></path>
                                </svg>
                                Download
                                @if($requiresPassword)
                                    <span class="text-xs opacity-75">(Password Required)</span>
                                @endif
                            </button>
                            
                            @can('delete', $document)
                                <form method="POST" action="{{ route('families.documents.destroy', ['family' => $family->id, 'document' => $document]) }}" onsubmit="return confirm('Delete this document?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm" class="text-red-600 hover:text-red-700">Delete</x-button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="col-span-1 md:col-span-2 xl:col-span-3 text-center py-12 bg-[var(--color-bg-secondary)] rounded-xl border border-dashed border-[var(--color-border-primary)]">
                        <p class="text-lg font-semibold text-[var(--color-text-primary)]">No documents yet</p>
                        <p class="text-sm text-[var(--color-text-secondary)]">Upload family IDs, passports, and insurance papers to keep them safe.</p>
                        @can('create', [\App\Models\Document::class, $family->id])
                            <div class="mt-4">
                                <a href="{{ route('families.documents.create', ['family' => $family->id]) }}">
                                    <x-button variant="primary" size="md">Upload Document</x-button>
                                </a>
                            </div>
                        @endcan
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $documents->links() }}
            </div>
        </div>
    </div>

    <form id="document-download-form" method="POST" class="hidden">
        @csrf
    </form>

    <!-- Password Modal -->
    <div id="password-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4 border border-[var(--color-border-primary)]">
            <div>
                <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-1">Enter Document Password</h3>
                <p class="text-sm text-[var(--color-text-secondary)]">This document is password protected. Please enter the password to download.</p>
            </div>
            <div>
                <label for="document-password" class="block text-sm font-medium text-[var(--color-text-primary)] mb-2">Password</label>
                <input id="document-password" type="password" class="w-full border border-[var(--color-border-primary)] rounded-lg px-4 py-2.5 bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Enter password" autocomplete="off">
                <p id="password-error" class="mt-2 text-sm text-red-600 hidden">Incorrect password. Please try again.</p>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" id="password-cancel" class="px-4 py-2 rounded-lg border border-[var(--color-border-primary)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">Cancel</button>
                <button type="button" id="password-submit" class="px-4 py-2 rounded-lg text-white bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] transition-colors">Verify & Download</button>
            </div>
        </div>
    </div>


</x-app-layout>

