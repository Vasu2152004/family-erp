<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StorePrescriptionRequest;
use App\Http\Requests\Health\UpdatePrescriptionRequest;
use App\Models\Family;
use App\Models\DoctorVisit;
use App\Models\Prescription;
use App\Services\HealthService;
use App\Support\BlobPathNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PrescriptionController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService
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

        $path = BlobPathNormalizer::normalize($prescription->file_path);
        if ($path !== null && $path !== '') {
            try {
                Storage::disk('vercel_blob')->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Blob delete failed on prescription destroy', ['prescription_id' => $prescription->id, 'message' => $e->getMessage()]);
            }
        }

        $prescription->delete();

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Prescription deleted successfully.');
    }

    public function download(Request $request, Family $family, DoctorVisit $visit, Prescription $prescription)
    {
        $this->authorize('view', $prescription);

        $path = BlobPathNormalizer::normalize($prescription->file_path);
        if ($path === null || $path === '') {
            Log::warning('Blob path empty after normalization', ['prescription_id' => $prescription->id]);
            abort(404, 'Prescription file not found.');
        }

        try {
            $url = Storage::disk('vercel_blob')->temporaryUrl($path, now()->addMinutes(15));
            return redirect($url);
        } catch (\Throwable $e) {
            Log::warning('Blob temporaryUrl failed', ['prescription_id' => $prescription->id, 'message' => $e->getMessage()]);
            abort(404, 'Prescription file not found.');
        }
    }
}
