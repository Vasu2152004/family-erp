<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StoreMedicalRecordRequest;
use App\Http\Requests\Health\UpdateMedicalRecordRequest;
use App\Models\Family;
use App\Models\MedicalRecord;
use App\Models\FamilyMember;
use App\Services\HealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService
    ) {
    }

    public function index(Request $request, Family $family): View
    {
        $this->authorize('viewAny', MedicalRecord::class);

        $query = MedicalRecord::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->with(['familyMember', 'createdBy'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('primary_condition', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('record_type')) {
            $query->where('record_type', $request->string('record_type'));
        }

        if ($request->filled('family_member_id')) {
            $query->where('family_member_id', $request->integer('family_member_id'));
        }

        $records = $query->paginate(15)->appends($request->query());

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('health.records.index', [
            'family' => $family,
            'records' => $records,
            'members' => $members,
            'filters' => $request->only(['search', 'record_type', 'family_member_id']),
        ]);
    }

    public function create(Request $request, Family $family): View
    {
        $this->authorize('create', MedicalRecord::class);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('health.records.create', [
            'family' => $family,
            'members' => $members,
        ]);
    }

    public function store(StoreMedicalRecordRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('create', MedicalRecord::class);

        $record = $this->healthService->createMedicalRecord(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $request->user()->id
        );

        return redirect()->route('families.health.records.show', ['family' => $family->id, 'record' => $record->id])
            ->with('success', 'Medical record created successfully.');
    }

    public function show(Request $request, Family $family, MedicalRecord $record): View
    {
        $this->authorize('view', $record);

        $record->load(['familyMember', 'createdBy', 'updatedBy', 'doctorVisits']);

        return view('health.records.show', [
            'family' => $family,
            'record' => $record,
        ]);
    }

    public function edit(Request $request, Family $family, MedicalRecord $record): View
    {
        $this->authorize('update', $record);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('health.records.edit', [
            'family' => $family,
            'record' => $record,
            'members' => $members,
        ]);
    }

    public function update(UpdateMedicalRecordRequest $request, Family $family, MedicalRecord $record): RedirectResponse
    {
        $this->authorize('update', $record);

        $this->healthService->updateMedicalRecord(
            $record,
            $request->validated(),
            $request->user()->id
        );

        return redirect()->route('families.health.records.show', ['family' => $family->id, 'record' => $record->id])
            ->with('success', 'Medical record updated successfully.');
    }

    public function destroy(Request $request, Family $family, MedicalRecord $record): RedirectResponse
    {
        $this->authorize('delete', $record);

        $record->delete();

        return redirect()->route('families.health.records.index', ['family' => $family->id])
            ->with('success', 'Medical record deleted successfully.');
    }
}
