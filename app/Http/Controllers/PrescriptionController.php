<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StorePrescriptionRequest;
use App\Http\Requests\Health\UpdatePrescriptionRequest;
use App\Models\DoctorVisit;
use App\Models\Family;
use App\Models\Prescription;
use App\Services\HealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PrescriptionController extends Controller
{
    public function __construct(private readonly HealthService $healthService)
    {
    }

    public function store(StorePrescriptionRequest $request, Family $family, DoctorVisit $visit): RedirectResponse
    {
        $this->abortIfFamilyMismatch($visit->family_id, $family->id);
        $this->authorize('create', [Prescription::class, $visit]);

        $prescription = $this->healthService->createPrescription(
            $visit,
            $request->validated() + ['attachment' => $request->file('attachment')],
            auth()->user()->tenant_id,
            auth()->id()
        );

        return redirect()
            ->route('families.health.visits.show', [$family, $visit])
            ->with('success', 'Prescription added successfully.');
    }

    public function update(UpdatePrescriptionRequest $request, Family $family, DoctorVisit $visit, Prescription $prescription): RedirectResponse
    {
        $this->abortIfFamilyMismatch($visit->family_id, $family->id);
        $this->abortIfFamilyMismatch($prescription->family_id, $family->id);
        $this->authorize('update', $prescription);

        $this->healthService->updatePrescription(
            $prescription,
            $request->validated() + ['attachment' => $request->file('attachment')],
            auth()->id()
        );

        return redirect()
            ->route('families.health.visits.show', [$family, $visit])
            ->with('success', 'Prescription updated successfully.');
    }

    public function destroy(Family $family, DoctorVisit $visit, Prescription $prescription): RedirectResponse
    {
        $this->abortIfFamilyMismatch($visit->family_id, $family->id);
        $this->abortIfFamilyMismatch($prescription->family_id, $family->id);
        $this->authorize('delete', $prescription);

        if ($prescription->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($prescription->file_path);
        }
        $prescription->delete();

        return redirect()
            ->route('families.health.visits.show', [$family, $visit])
            ->with('success', 'Prescription removed.');
    }

    public function download(Family $family, DoctorVisit $visit, Prescription $prescription): Response
    {
        $this->abortIfFamilyMismatch($visit->family_id, $family->id);
        $this->abortIfFamilyMismatch($prescription->family_id, $family->id);
        $this->authorize('view', $prescription);

        if (!$prescription->file_path || !Storage::disk('public')->exists($prescription->file_path)) {
            abort(404, 'Prescription attachment not found.');
        }

        return Storage::disk('public')->download(
            $prescription->file_path,
            $prescription->original_name ?? 'prescription.pdf'
        );
    }

    private function abortIfFamilyMismatch(int $modelFamilyId, int $familyId): void
    {
        abort_unless($modelFamilyId === $familyId, 404);
    }
}

