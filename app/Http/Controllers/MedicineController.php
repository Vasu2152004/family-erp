<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Medicines\StoreMedicineRequest;
use App\Http\Requests\Medicines\UpdateMedicineRequest;
use App\Models\Family;
use App\Models\Medicine;
use App\Models\FamilyMember;
use App\Models\Prescription;
use App\Services\MedicineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Carbon\Carbon;

class MedicineController extends Controller
{
    public function __construct(
        private readonly MedicineService $medicineService
    ) {
    }

    public function index(Request $request, Family $family): View
    {
        $user = $request->user();
        
        // Optimize access check
        $hasAccess = \App\Models\FamilyUserRole::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->exists()
            || \App\Models\FamilyMember::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('viewAny', Medicine::class);

        $query = Medicine::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->with(['familyMember:id,first_name,last_name', 'prescription:id,medication_name', 'createdBy:id,name']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('batch_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('family_member_id')) {
            $query->where('family_member_id', $request->integer('family_member_id'));
        }

        if ($request->filled('expired')) {
            $query->where('expiry_date', '<', Carbon::today());
        }

        if ($request->filled('expiring_soon')) {
            $query->where('expiry_date', '<=', Carbon::today()->addDays(30))
                ->where('expiry_date', '>=', Carbon::today());
        }

        if ($request->filled('low_stock')) {
            $query->whereRaw('quantity < min_stock_level');
        }

        $query->orderBy('created_at', 'desc');

        $medicines = $query->paginate(10)->appends($request->query());

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('medicines.index', [
            'family' => $family,
            'medicines' => $medicines,
            'members' => $members,
            'filters' => $request->only(['search', 'family_member_id', 'expired', 'expiring_soon', 'low_stock']),
        ]);
    }

    public function create(Request $request, Family $family): View
    {
        $user = $request->user();
        
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('create', Medicine::class);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        // Get active prescriptions for optional linking
        $prescriptions = Prescription::where('family_id', $family->id)
            ->where('status', 'active')
            ->with('familyMember')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('medicines.create', [
            'family' => $family,
            'members' => $members,
            'prescriptions' => $prescriptions,
            'medicine' => null,
        ]);
    }

    public function store(StoreMedicineRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('create', Medicine::class);

        $file = $request->hasFile('prescription_file') ? $request->file('prescription_file') : null;

        $medicine = $this->medicineService->createMedicine(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $request->user()->id,
            $file
        );

        return redirect()->route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])
            ->with('success', 'Medicine created successfully.');
    }

    public function show(Request $request, Family $family, Medicine $medicine): View
    {
        $user = $request->user();
        
        // Quick access check - use direct queries
        $hasAccess = \App\Models\FamilyUserRole::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->exists()
            || \App\Models\FamilyMember::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        // Ensure medicine belongs to this family before loading relationships
        if ($medicine->family_id !== $family->id || $medicine->tenant_id !== $family->tenant_id) {
            abort(404, 'Medicine not found in this family.');
        }

        $this->authorize('view', $medicine);

        $medicine->load([
            'familyMember',
            'prescription.familyMember',
            'expiryReminders' => fn ($q) => $q->orderBy('remind_at'),
            'intakeReminders' => fn ($q) => $q->orderBy('reminder_time')->with('familyMember'),
            'createdBy:id,name,email',
            'updatedBy:id,name,email',
        ]);

        return view('medicines.show', [
            'family' => $family,
            'medicine' => $medicine,
        ]);
    }

    public function edit(Request $request, Family $family, Medicine $medicine): View
    {
        $user = $request->user();
        
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('update', $medicine);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        $prescriptions = Prescription::where('family_id', $family->id)
            ->where('status', 'active')
            ->with('familyMember')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('medicines.edit', [
            'family' => $family,
            'medicine' => $medicine,
            'members' => $members,
            'prescriptions' => $prescriptions,
        ]);
    }

    public function update(UpdateMedicineRequest $request, Family $family, Medicine $medicine): RedirectResponse
    {
        $this->authorize('update', $medicine);

        $file = $request->hasFile('prescription_file') ? $request->file('prescription_file') : null;

        $this->medicineService->updateMedicine(
            $medicine,
            $request->validated(),
            $request->user()->id,
            $file
        );

        return redirect()->route('families.medicines.show', ['family' => $family->id, 'medicine' => $medicine->id])
            ->with('success', 'Medicine updated successfully.');
    }

    public function destroy(Request $request, Family $family, Medicine $medicine): RedirectResponse
    {
        $this->authorize('delete', $medicine);

        $this->medicineService->deleteMedicine($medicine);

        return redirect()->route('families.medicines.index', ['family' => $family->id])
            ->with('success', 'Medicine deleted successfully.');
    }

    public function downloadPrescription(Request $request, Family $family, Medicine $medicine)
    {
        $this->authorize('view', $medicine);

        if (!$medicine->prescription_file_path || !Storage::disk('local')->exists($medicine->prescription_file_path)) {
            abort(404, 'Prescription file not found.');
        }

        return Storage::disk('local')->download(
            $medicine->prescription_file_path,
            $medicine->prescription_original_name ?? 'prescription.pdf'
        );
    }
}
