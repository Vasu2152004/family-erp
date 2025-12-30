<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Medicines\StoreMedicineIntakeReminderRequest;
use App\Http\Requests\Medicines\UpdateMedicineIntakeReminderRequest;
use App\Models\Family;
use App\Models\Medicine;
use App\Models\MedicineIntakeReminder;
use App\Services\MedicineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MedicineIntakeReminderController extends Controller
{
    public function __construct(
        private readonly MedicineService $medicineService
    ) {
    }

    public function store(StoreMedicineIntakeReminderRequest $request, Family $family, Medicine $medicine): RedirectResponse
    {
        // Ensure medicine belongs to the family
        if ($medicine->family_id !== $family->id || $medicine->tenant_id !== $family->tenant_id) {
            abort(404, 'Medicine not found in this family.');
        }

        $this->authorize('update', $medicine);

        try {
            $this->medicineService->createIntakeReminder(
                $request->validated(),
                $family->tenant_id,
                $family->id,
                $medicine,
                $request->user()->id
            );

            return redirect()->route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])
                ->with('success', 'Intake reminder created successfully.');
        } catch (\Exception $e) {
            return redirect()->route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])
                ->withErrors(['error' => 'Failed to create reminder: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(UpdateMedicineIntakeReminderRequest $request, Family $family, Medicine $medicine, MedicineIntakeReminder $reminder): RedirectResponse
    {
        // Ensure reminder belongs to the medicine and family
        if ($reminder->medicine_id !== $medicine->id || $reminder->family_id !== $family->id) {
            abort(404, 'Reminder not found.');
        }

        $this->authorize('update', $medicine);

        try {
            $this->medicineService->updateIntakeReminder(
                $reminder,
                $request->validated(),
                $request->user()->id
            );

            return redirect()->route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])
                ->with('success', 'Intake reminder updated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])
                ->withErrors(['error' => 'Failed to update reminder: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Request $request, Family $family, Medicine $medicine, MedicineIntakeReminder $reminder): RedirectResponse
    {
        // Ensure reminder belongs to the medicine and family
        if ($reminder->medicine_id !== $medicine->id || $reminder->family_id !== $family->id) {
            abort(404, 'Reminder not found.');
        }

        $this->authorize('update', $medicine);

        $this->medicineService->deleteIntakeReminder($reminder);

        return redirect()->route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])
            ->with('success', 'Intake reminder deleted successfully.');
    }

    public function toggleStatus(Request $request, Family $family, Medicine $medicine, MedicineIntakeReminder $reminder): RedirectResponse
    {
        // Ensure reminder belongs to the medicine and family
        if ($reminder->medicine_id !== $medicine->id || $reminder->family_id !== $family->id) {
            abort(404, 'Reminder not found.');
        }

        $this->authorize('update', $medicine);

        $newStatus = $reminder->status === 'active' ? 'paused' : 'active';
        $reminder->update(['status' => $newStatus]);

        // Recalculate next_run_at if activating
        if ($newStatus === 'active') {
            $nextRunAt = $reminder->calculateNextRunAt();
            $reminder->update(['next_run_at' => $nextRunAt]);
        }

        return redirect()->route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])
            ->with('success', "Intake reminder {$newStatus} successfully.");
    }
}






