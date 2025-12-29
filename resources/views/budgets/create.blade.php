<x-app-layout title="Create Budget: {{ $family->name }}">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Finance', 'url' => route('finance.index', ['family_id' => $family->id])],
            ['label' => 'Budgets', 'url' => route('finance.budgets.index', ['family_id' => $family->id])],
            ['label' => 'Create Budget']
        ]" />

        <div class="bg-[var(--color-bg-primary)] rounded-xl shadow-lg border border-[var(--color-border-primary)] p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Create Budget</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Set a monthly budget for {{ $family->name }}
                </p>
            </div>

        <x-form method="POST" action="{{ route('finance.budgets.store', ['family_id' => $family->id]) }}" class="space-y-6">
            @csrf
            <input type="hidden" name="family_id" value="{{ $family->id }}">

            <div>
                <x-label for="family_member_id">Budget For</x-label>
                <select name="family_member_id" id="family_member_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="">Family Budget (All Members)</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" {{ old('family_member_id') == $member->id ? 'selected' : '' }}>
                            {{ $member->user->name }} (Personal Budget)
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Select a member for personal budget, or leave empty for family budget</p>
                <x-error-message field="family_member_id" />
            </div>

            <div>
                <x-label for="category_id">Category</x-label>
                <select name="category_id" id="category_id" class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                    <option value="">Total Budget (All Categories)</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Leave empty for total budget</p>
                <x-error-message field="category_id" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-label for="month" required>Month</x-label>
                    <select name="month" id="month" required class="mt-1 block w-full rounded-lg border border-[var(--color-border-primary)] px-4 py-2.5 text-[var(--color-text-primary)] bg-[var(--color-bg-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ old('month', now()->month) == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $i, 1)->format('F') }}</option>
                        @endfor
                    </select>
                    <x-error-message field="month" />
                </div>

                <div>
                    <x-label for="year" required>Year</x-label>
                    <x-input 
                        type="number" 
                        name="year" 
                        id="year" 
                        value="{{ old('year', now()->year) }}" 
                        min="2000"
                        max="2100"
                        required
                        class="mt-1"
                    />
                    <x-error-message field="year" />
                </div>
            </div>

            <div>
                <x-label for="amount" required>Budget Amount</x-label>
                <x-input 
                    type="number" 
                    name="amount" 
                    id="amount" 
                    value="{{ old('amount') }}" 
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    required
                    class="mt-1"
                />
                <x-error-message field="amount" />
            </div>

            <div>
                <x-label for="alert_threshold">Alert Threshold (%)</x-label>
                <x-input 
                    type="number" 
                    name="alert_threshold" 
                    id="alert_threshold" 
                    value="{{ old('alert_threshold') }}" 
                    step="0.01"
                    min="0"
                    max="100"
                    placeholder="e.g., 80 (alert at 80% of budget)"
                    class="mt-1"
                />
                <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Optional: Get notified when budget reaches this percentage</p>
                <x-error-message field="alert_threshold" />
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-[var(--color-border-primary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                <x-label for="is_active" class="ml-2">Active</x-label>
            </div>

            <div class="flex gap-4">
                <x-button type="submit" variant="primary" size="md">
                    Create Budget
                </x-button>
                <a href="{{ route('finance.budgets.index', ['family_id' => $family->id]) }}">
                    <x-button type="button" variant="outline" size="md">
                        Cancel
                    </x-button>
                </a>
            </div>
        </x-form>
        </div>
    </div>
</x-app-layout>


