<x-app-layout title="Add Medicine">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Medicines', 'url' => route('families.medicines.index', ['family' => $family->id])],
            ['label' => 'Add Medicine'],
        ]" />

        <div class="card card-contrast">
            <p class="pill mb-3 w-fit">💊 Medicine Management</p>
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Add Medicine</h2>

            <form method="POST" action="{{ route('families.medicines.store', ['family' => $family->id]) }}" enctype="multipart/form-data">
                @csrf
                @include('medicines._form')
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Create Medicine</x-button>
                    <a href="{{ route('families.medicines.index', ['family' => $family->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>


