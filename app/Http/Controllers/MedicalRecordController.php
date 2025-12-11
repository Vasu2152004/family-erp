<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StoreMedicalRecordRequest;
use App\Http\Requests\Health\UpdateMedicalRecordRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MedicalRecord;
use App\Services\HealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    public function __construct(private readonly HealthService $healthService)
    {
    }

    public function index(Family $family): View
    {
        $this->authorize('viewAny', [MedicalRecord::class, $family]);

        $records = MedicalRecord::with(['familyMember'])
            ->where('family_id', $family->id)
            ->forTenant(auth()->user()->tenant_id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        $members = FamilyMember::where('family_id', $family->id)
            ->alive()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('health.records.index', compact('family', 'records', 'members'));
    }

    public function create(Family $family): View
    {
        $this->authorize('create', [MedicalRecord::class, $family]);

        $members = FamilyMember::where('family_id', $family->id)
            ->alive()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('health.records.create', compact('family', 'members'));
    }

    public function store(StoreMedicalRecordRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('create', [MedicalRecord::class, $family]);

        $record = $this->healthService->createMedicalRecord(
            $request->validated(),
            $family,
            auth()->user()->tenant_id,
            auth()->id()
        );

        return redirect()
            ->route('families.health.records.index', $family)
            ->with('success', 'Medical record created successfully.');
    }

    public function show(Family $family, MedicalRecord $record): View
    {
        $this->abortIfFamilyMismatch($record->family_id, $family->id);
        $this->authorize('view', $record);

        $record->load([
            'familyMember',
            'visits' => fn ($q) => $q->orderByDesc('visit_date')->limit(10),
        ]);

        return view('health.records.show', compact('family', 'record'));
    }

    public function edit(Family $family, MedicalRecord $record): View
    {
        $this->abortIfFamilyMismatch($record->family_id, $family->id);
        $this->authorize('update', $record);

        $members = FamilyMember::where('family_id', $family->id)
            ->alive()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('health.records.edit', compact('family', 'record', 'members'));
    }

    public function update(UpdateMedicalRecordRequest $request, Family $family, MedicalRecord $record): RedirectResponse
    {
        $this->abortIfFamilyMismatch($record->family_id, $family->id);
        $this->authorize('update', $record);

        $this->healthService->updateMedicalRecord($record, $request->validated(), auth()->id());

        return redirect()
            ->route('families.health.records.index', $family)
            ->with('success', 'Medical record updated successfully.');
    }

    public function destroy(Family $family, MedicalRecord $record): RedirectResponse
    {
        $this->abortIfFamilyMismatch($record->family_id, $family->id);
        $this->authorize('delete', $record);
        $record->delete();

        return redirect()
            ->route('families.health.records.index', $family)
            ->with('success', 'Medical record deleted successfully.');
    }

    private function abortIfFamilyMismatch(int $modelFamilyId, int $familyId): void
    {
        abort_unless($modelFamilyId === $familyId, 404);
    }
}

