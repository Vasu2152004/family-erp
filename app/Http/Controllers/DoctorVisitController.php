<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StoreDoctorVisitRequest;
use App\Http\Requests\Health\UpdateDoctorVisitRequest;
use App\Models\DoctorVisit;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MedicalRecord;
use App\Services\HealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorVisitController extends Controller
{
    public function __construct(private readonly HealthService $healthService)
    {
    }

    public function index(Request $request, Family $family): View
    {
        $this->authorize('viewAny', [DoctorVisit::class, $family]);

        $filters = $request->only(['status', 'family_member_id']);

        $visits = DoctorVisit::with(['familyMember', 'medicalRecord'])
            ->where('family_id', $family->id)
            ->forTenant(auth()->user()->tenant_id)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['family_member_id'] ?? null, fn ($q, $memberId) => $q->where('family_member_id', $memberId))
            ->orderByDesc('visit_date')
            ->paginate(12)
            ->appends($filters);

        $members = FamilyMember::where('family_id', $family->id)
            ->alive()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('health.visits.index', compact('family', 'visits', 'members', 'filters'));
    }

    public function create(Family $family): View
    {
        $this->authorize('create', [DoctorVisit::class, $family]);

        $members = FamilyMember::where('family_id', $family->id)
            ->alive()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $records = MedicalRecord::where('family_id', $family->id)
            ->orderByDesc('recorded_at')
            ->get(['id', 'title', 'family_member_id']);

        return view('health.visits.create', compact('family', 'members', 'records'));
    }

    public function store(StoreDoctorVisitRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('create', [DoctorVisit::class, $family]);

        $visit = $this->healthService->createDoctorVisit(
            $request->validated(),
            $family,
            auth()->user()->tenant_id,
            auth()->id()
        );

        return redirect()
            ->route('families.health.visits.show', [$family, $visit])
            ->with('success', 'Doctor visit logged successfully.');
    }

    public function show(Family $family, DoctorVisit $visit): View
    {
        $this->abortIfFamilyMismatch($visit->family_id, $family->id);
        $this->authorize('view', $visit);

        $visit->load([
            'familyMember',
            'medicalRecord',
            'prescriptions.reminders',
        ]);

        $members = FamilyMember::where('family_id', $family->id)
            ->alive()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $records = MedicalRecord::where('family_id', $family->id)
            ->orderByDesc('recorded_at')
            ->get(['id', 'title', 'family_member_id']);

        return view('health.visits.show', compact('family', 'visit', 'members', 'records'));
    }

    public function edit(Family $family, DoctorVisit $visit): View
    {
        $this->abortIfFamilyMismatch($visit->family_id, $family->id);
        $this->authorize('update', $visit);

        $members = FamilyMember::where('family_id', $family->id)
            ->alive()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $records = MedicalRecord::where('family_id', $family->id)
            ->orderByDesc('recorded_at')
            ->get(['id', 'title', 'family_member_id']);

        return view('health.visits.edit', compact('family', 'visit', 'members', 'records'));
    }

    public function update(UpdateDoctorVisitRequest $request, Family $family, DoctorVisit $visit): RedirectResponse
    {
        $this->abortIfFamilyMismatch($visit->family_id, $family->id);
        $this->authorize('update', $visit);

        $this->healthService->updateDoctorVisit($visit, $request->validated(), auth()->id());

        return redirect()
            ->route('families.health.visits.show', [$family, $visit])
            ->with('success', 'Visit updated successfully.');
    }

    public function destroy(Family $family, DoctorVisit $visit): RedirectResponse
    {
        $this->abortIfFamilyMismatch($visit->family_id, $family->id);
        $this->authorize('delete', $visit);
        $visit->delete();

        return redirect()
            ->route('families.health.visits.index', $family)
            ->with('success', 'Visit deleted successfully.');
    }

    private function abortIfFamilyMismatch(int $modelFamilyId, int $familyId): void
    {
        abort_unless($modelFamilyId === $familyId, 404);
    }
}

