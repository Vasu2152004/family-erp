<x-app-layout title="Edit Medicine">
    <div class="space-y-6">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => $family->name, 'url' => route('families.show', $family)],
            ['label' => 'Medicines', 'url' => route('families.medicines.index', ['family' => $family->id])],
            ['label' => $medicine->name, 'url' => route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])],
            ['label' => 'Edit'],
        ]" />

        <div class="card card-contrast">
            <p class="pill mb-3 w-fit">💊 Medicine Management</p>
            <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Edit Medicine</h2>

            <x-form method="POST" action="{{ route('families.medicines.update', ['family' => $family->id, 'medicine' => $medicine->id]) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                @include('medicines._form')
                <div class="mt-6 flex gap-3">
                    <x-button type="submit" variant="primary" size="md">Update Medicine</x-button>
                    <a href="{{ route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id]) }}">
                        <x-button variant="ghost" size="md">Cancel</x-button>
                    </a>
                </div>
            </x-form>
        </div>
    </div>
</x-app-layout>





