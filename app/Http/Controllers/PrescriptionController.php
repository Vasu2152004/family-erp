<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StorePrescriptionRequest;
use App\Http\Requests\Health\UpdatePrescriptionRequest;
use App\Models\Family;
use App\Models\DoctorVisit;
use App\Models\Prescription;
use App\Services\HealthService;
use App\Services\VercelBlobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService,
        private readonly VercelBlobService $vercelBlob
    ) {
    }

    public function store(StorePrescriptionRequest $request, Family $family, DoctorVisit $visit): RedirectResponse
    {
        $this->authorize('create', Prescription::class);

        $file = $request->hasFile('attachment') ? $request->file('attachment') : null;

        $prescription = $this->healthService->createPrescription(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $visit,
            $request->user()->id,
            $file
        );

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Prescription added successfully.');
    }

    public function update(UpdatePrescriptionRequest $request, Family $family, DoctorVisit $visit, Prescription $prescription): RedirectResponse
    {
        $this->authorize('update', $prescription);

        $file = $request->hasFile('attachment') ? $request->file('attachment') : null;

        $this->healthService->updatePrescription(
            $prescription,
            $request->validated(),
            $request->user()->id,
            $file
        );

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Prescription updated successfully.');
    }

    public function destroy(Request $request, Family $family, DoctorVisit $visit, Prescription $prescription): RedirectResponse
    {
        $this->authorize('delete', $prescription);

        if (!empty($prescription->file_path) && str_starts_with((string) $prescription->file_path, 'https://')) {
            $this->vercelBlob->delete($prescription->file_path);
        }

        $prescription->delete();

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Prescription deleted successfully.');
    }

    public function download(Request $request, Family $family, DoctorVisit $visit, Prescription $prescription)
    {
        $this->authorize('view', $prescription);

        $url = $prescription->file_path;
        if (empty($url) || !str_starts_with((string) $url, 'https://')) {
            abort(404, 'Prescription file not found.');
        }

        return redirect($url);
    }
}
