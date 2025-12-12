<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StoreMedicineReminderRequest;
use App\Http\Requests\Health\UpdateMedicineReminderRequest;
use App\Models\Family;
use App\Models\DoctorVisit;
use App\Models\Prescription;
use App\Models\MedicineReminder;
use App\Services\HealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MedicineReminderController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService
    ) {
    }

    public function store(StoreMedicineReminderRequest $request, Family $family, DoctorVisit $visit, Prescription $prescription): RedirectResponse
    {
        $this->authorize('create', MedicineReminder::class);

        $reminder = $this->healthService->createMedicineReminder(
            $request->validated(),
            $request->user()->tenant_id,
            $family->id,
            $prescription,
            $request->user()->id
        );

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Medicine reminder created successfully.');
    }

    public function update(UpdateMedicineReminderRequest $request, Family $family, DoctorVisit $visit, Prescription $prescription, MedicineReminder $reminder): RedirectResponse
    {
        $this->authorize('update', $reminder);

        $this->healthService->updateMedicineReminder(
            $reminder,
            $request->validated(),
            $request->user()->id
        );

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Medicine reminder updated successfully.');
    }

    public function destroy(Request $request, Family $family, DoctorVisit $visit, Prescription $prescription, MedicineReminder $reminder): RedirectResponse
    {
        $this->authorize('delete', $reminder);

        $reminder->delete();

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Medicine reminder deleted successfully.');
    }
}
