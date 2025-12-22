<x-app-layout title="Medicines: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Medicines'],
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Medicines</h1>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                        Manage medicines, track stock, and set reminders for {{ $family->name }}
                    </p>
                </div>
                @can('create', \App\Models\Medicine::class)
                    <a href="{{ route('families.medicines.create', ['family' => $family->id]) }}">
                        <x-button variant="primary" size="md">Add Medicine</x-button>
                    </a>
                @endcan
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('families.medicines.index', ['family' => $family->id]) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-[var(--color-bg-secondary)] border border-[var(--color-border-primary)] rounded-xl p-4">
                <div>
                    <x-label for="search">Search</x-label>
                    <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, manufacturer, batch..." class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                </div>
                <div>
                    <x-label for="family_member_id">Member</x-label>
                    <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        <option value="">All members</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" {{ ($filters['family_member_id'] ?? '') == $member->id ? 'selected' : '' }}>
                                {{ $member->first_name }} {{ $member->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="expired" value="1" {{ ($filters['expired'] ?? '') == '1' ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)]">
                        <span class="text-sm text-[var(--color-text-secondary)]">Expired</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="expiring_soon" value="1" {{ ($filters['expiring_soon'] ?? '') == '1' ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)]">
                        <span class="text-sm text-[var(--color-text-secondary)]">Expiring Soon</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="low_stock" value="1" {{ ($filters['low_stock'] ?? '') == '1' ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)]">
                        <span class="text-sm text-[var(--color-text-secondary)]">Low Stock</span>
                    </label>
                </div>
                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="outline" size="md">Apply</x-button>
                    <a href="{{ route('families.medicines.index', ['family' => $family->id]) }}">
                        <x-button type="button" variant="outline" size="md">Reset</x-button>
                    </a>
                </div>
            </form>

            @if($medicines->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border-primary)]">
                        <thead class="bg-[var(--color-bg-secondary)]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Medicine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Expiry Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--color-bg-primary)] divide-y divide-[var(--color-border-primary)]">
                            @foreach($medicines as $medicine)
                                <tr class="hover:bg-[var(--color-bg-secondary)] transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-[var(--color-text-primary)]">{{ $medicine->name }}</span>
                                            @if($medicine->prescription_file_path)
                                                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">📄 Prescription</span>
                                            @endif
                                        </div>
                                        @if($medicine->manufacturer)
                                            <p class="text-xs text-[var(--color-text-secondary)] mt-1">{{ $medicine->manufacturer }}</p>
                                        @endif
                                        @if($medicine->familyMember)
                                            <p class="text-xs text-[var(--color-text-secondary)] mt-1">For: {{ $medicine->familyMember->first_name }} {{ $medicine->familyMember->last_name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $qty = $medicine->quantity == (int)$medicine->quantity ? (int)$medicine->quantity : number_format((float)$medicine->quantity, 2);
                                        @endphp
                                        <span class="text-sm text-[var(--color-text-primary)]">{{ $qty }} {{ $medicine->unit }}</span>
                                        @if($medicine->isLowStock())
                                            <span class="ml-2 text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">⚠️ Low Stock</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($medicine->expiry_date)
                                            @php
                                                $daysUntilExpiry = (int)now()->diffInDays($medicine->expiry_date, false);
                                            @endphp
                                            <span class="text-sm text-[var(--color-text-primary)]">{{ $medicine->expiry_date->format('M d, Y') }}</span>
                                            @if($daysUntilExpiry < 0)
                                                <span class="ml-2 text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">Expired</span>
                                            @elseif($daysUntilExpiry <= 30)
                                                <span class="ml-2 text-xs px-2 py-1 rounded-full bg-orange-100 text-orange-800">{{ $daysUntilExpiry }} days left</span>
                                            @else
                                                <span class="ml-2 text-xs text-[var(--color-text-secondary)]">{{ $daysUntilExpiry }} days left</span>
                                            @endif
                                        @else
                                            <span class="text-sm text-[var(--color-text-secondary)]">No expiry</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            @if($medicine->isExpired())
                                                <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800 w-fit">Expired</span>
                                            @elseif($medicine->isLowStock())
                                                <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 w-fit">Low Stock</span>
                                            @else
                                                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800 w-fit">Active</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            <a href="{{ route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">View</a>
                                            @can('update', $medicine)
                                                <a href="{{ route('families.medicines.edit', ['family' => $family->id, 'medicine' => $medicine->id]) }}" class="text-[var(--color-primary)] hover:text-[var(--color-primary-dark)]">Edit</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $medicines->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--color-text-secondary)]">No medicines found.</p>
                    @can('create', \App\Models\Medicine::class)
                        <a href="{{ route('families.medicines.create', ['family' => $family->id]) }}" class="mt-4 inline-block">
                            <x-button variant="primary" size="md">Add Your First Medicine</x-button>
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
