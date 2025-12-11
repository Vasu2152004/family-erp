<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StoreMedicineReminderRequest;
use App\Http\Requests\Health\UpdateMedicineReminderRequest;
use App\Models\DoctorVisit;
use App\Models\Family;
use App\Models\MedicineReminder;
use App\Models\Prescription;
use App\Services\HealthService;
use Illuminate\Http\RedirectResponse;

class MedicineReminderController extends Controller
{
    public function __construct(private readonly HealthService $healthService)
    {
    }

    public function store(StoreMedicineReminderRequest $request, Family $family, DoctorVisit $visit, Prescription $prescription): RedirectResponse
    {
        $this->assertFamily($family, $visit, $prescription);
        $this->authorize('create', [MedicineReminder::class, $prescription]);

        $data = $request->validated();
        $data['prescription_id'] = $prescription->id;

        $this->healthService->createOrUpdateReminder(
            $prescription,
            $data,
            auth()->user()->tenant_id,
            auth()->id()
        );

        return redirect()
            ->route('families.health.visits.show', [$family, $visit])
            ->with('success', 'Reminder added successfully.');
    }

    public function update(UpdateMedicineReminderRequest $request, Family $family, DoctorVisit $visit, Prescription $prescription, MedicineReminder $reminder): RedirectResponse
    {
        $this->assertFamily($family, $visit, $prescription, $reminder);
        $this->authorize('update', $reminder);

        $data = $request->validated();
        $data['prescription_id'] = $prescription->id;

        $this->healthService->createOrUpdateReminder(
            $prescription,
            $data,
            auth()->user()->tenant_id,
            auth()->id(),
            $reminder
        );

        return redirect()
            ->route('families.health.visits.show', [$family, $visit])
            ->with('success', 'Reminder updated successfully.');
    }

    public function destroy(Family $family, DoctorVisit $visit, Prescription $prescription, MedicineReminder $reminder): RedirectResponse
    {
        $this->assertFamily($family, $visit, $prescription, $reminder);
        $this->authorize('delete', $reminder);
        $reminder->delete();

        return redirect()
            ->route('families.health.visits.show', [$family, $visit])
            ->with('success', 'Reminder deleted.');
    }

    private function assertFamily(Family $family, DoctorVisit $visit, Prescription $prescription, ?MedicineReminder $reminder = null): void
    {
        abort_unless($visit->family_id === $family->id, 404);
        abort_unless($prescription->family_id === $family->id, 404);
        abort_unless($prescription->doctor_visit_id === $visit->id, 404);
        if ($reminder) {
            abort_unless($reminder->family_id === $family->id, 404);
            abort_unless($reminder->prescription_id === $prescription->id, 404);
        }
    }
}

