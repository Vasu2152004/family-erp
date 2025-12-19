<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Health\StoreDoctorVisitRequest;
use App\Http\Requests\Health\UpdateDoctorVisitRequest;
use App\Models\Family;
use App\Models\DoctorVisit;
use App\Models\FamilyMember;
use App\Models\MedicalRecord;
use App\Services\HealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorVisitController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService
    ) {
    }

    public function index(Request $request, Family $family): View
    {
        $this->authorize('viewAny', DoctorVisit::class);

        $query = DoctorVisit::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->with(['familyMember', 'medicalRecord', 'createdBy']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('doctor_name', 'like', "%{$search}%")
                    ->orWhere('clinic_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('visit_type')) {
            $query->where('visit_type', $request->string('visit_type'));
        }

        if ($request->filled('family_member_id')) {
            $query->where('family_member_id', $request->integer('family_member_id'));
        }

        // Filter for upcoming visits only
        if ($request->filled('upcoming') && $request->boolean('upcoming')) {
            $query->where(function ($q) {
                $q->where('visit_date', '>', \Carbon\Carbon::today())
                    ->orWhere(function ($todayQuery) {
                        $todayQuery->where('visit_date', \Carbon\Carbon::today());
                        if (\Schema::hasColumn('doctor_visits', 'visit_time')) {
                            $todayQuery->whereNotNull('visit_time')
                                ->where('visit_time', '>', \Carbon\Carbon::now()->format('H:i:s'));
                        }
                    });
            });
            
            // Ordering for upcoming visits (ascending by date/time)
            $query->orderBy('visit_date', 'asc');
            if (\Schema::hasColumn('doctor_visits', 'visit_time')) {
                $query->orderBy('visit_time', 'asc');
            }
        } else {
            // Default ordering for all visits (descending by date/time)
            $query->orderBy('visit_date', 'desc');
            if (\Schema::hasColumn('doctor_visits', 'visit_time')) {
                $query->orderBy('visit_time', 'desc');
            }
        }

        $visits = $query->paginate(10)->appends($request->query());

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('health.visits.index', [
            'family' => $family,
            'visits' => $visits,
            'members' => $members,
            'filters' => $request->only(['search', 'visit_type', 'family_member_id', 'upcoming']),
        ]);
    }

    public function create(Request $request, Family $family): View
    {
        $this->authorize('create', DoctorVisit::class);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        $medicalRecords = MedicalRecord::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->with('familyMember')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('health.visits.create', [
            'family' => $family,
            'members' => $members,
            'medicalRecords' => $medicalRecords,
        ]);
    }

    public function store(StoreDoctorVisitRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('create', DoctorVisit::class);

        $visit = $this->healthService->createDoctorVisit(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $request->user()->id
        );

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Doctor visit created successfully.');
    }

    public function show(Request $request, Family $family, DoctorVisit $visit): View
    {
        $this->authorize('view', $visit);

        $visit->load(['familyMember', 'medicalRecord', 'createdBy', 'updatedBy', 'prescriptions.familyMember', 'prescriptions.reminders']);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('health.visits.show', [
            'family' => $family,
            'visit' => $visit,
            'members' => $members,
        ]);
    }

    public function edit(Request $request, Family $family, DoctorVisit $visit): View
    {
        $this->authorize('update', $visit);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        $medicalRecords = MedicalRecord::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->with('familyMember')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('health.visits.edit', [
            'family' => $family,
            'visit' => $visit,
            'members' => $members,
            'medicalRecords' => $medicalRecords,
        ]);
    }

    public function update(UpdateDoctorVisitRequest $request, Family $family, DoctorVisit $visit): RedirectResponse
    {
        $this->authorize('update', $visit);

        $this->healthService->updateDoctorVisit(
            $visit,
            $request->validated(),
            $request->user()->id
        );

        return redirect()->route('families.health.visits.show', ['family' => $family->id, 'visit' => $visit->id])
            ->with('success', 'Doctor visit updated successfully.');
    }

    public function destroy(Request $request, Family $family, DoctorVisit $visit): RedirectResponse
    {
        $this->authorize('delete', $visit);

        $visit->delete();

        return redirect()->route('families.health.visits.index', ['family' => $family->id])
            ->with('success', 'Doctor visit deleted successfully.');
    }
}
