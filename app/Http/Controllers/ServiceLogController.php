<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Vehicles\StoreServiceLogRequest;
use App\Http\Requests\Vehicles\UpdateServiceLogRequest;
use App\Models\Family;
use App\Models\Vehicle;
use App\Models\ServiceLog;
use App\Models\Budget;
use App\Models\FamilyMember;
use App\Services\VehicleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceLogController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService
    ) {
    }

    public function index(Request $request, Family $family, Vehicle $vehicle): View
    {
        $this->authorize('view', $vehicle);

        $query = ServiceLog::where('vehicle_id', $vehicle->id)
            ->where('tenant_id', $family->tenant_id)
            ->where('family_id', $family->id)
            ->with(['createdBy', 'updatedBy']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('service_center_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->string('service_type'));
        }

        $query->latestFirst();

        $serviceLogs = $query->simplePaginate(10)->appends($request->query());

        return view('vehicles.service-logs.index', [
            'family' => $family,
            'vehicle' => $vehicle,
            'serviceLogs' => $serviceLogs,
            'filters' => $request->only(['search', 'service_type']),
        ]);
    }

    public function create(Request $request, Family $family, Vehicle $vehicle): View
    {
        $this->authorize('view', $vehicle);

        $budgets = $this->getBudgetsForFamily($family);

        return view('vehicles.service-logs.create', [
            'family' => $family,
            'vehicle' => $vehicle,
            'budgets' => $budgets,
        ]);
    }

    public function store(StoreServiceLogRequest $request, Family $family, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('view', $vehicle);

        $serviceLog = $this->vehicleService->createServiceLog(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $vehicle,
            $request->user()->id
        );

        return redirect()->route('families.vehicles.service-logs.index', ['family' => $family->id, 'vehicle' => $vehicle->id])
            ->with('success', 'Service log created successfully.');
    }

    public function edit(Request $request, Family $family, Vehicle $vehicle, ServiceLog $serviceLog): View
    {
        $this->authorize('view', $vehicle);

        $budgets = $this->getBudgetsForFamily($family);

        return view('vehicles.service-logs.edit', [
            'family' => $family,
            'vehicle' => $vehicle,
            'serviceLog' => $serviceLog,
            'budgets' => $budgets,
        ]);
    }

    public function update(UpdateServiceLogRequest $request, Family $family, Vehicle $vehicle, ServiceLog $serviceLog): RedirectResponse
    {
        $this->authorize('view', $vehicle);

        $this->vehicleService->updateServiceLog(
            $serviceLog,
            $request->validated(),
            $request->user()->id
        );

        return redirect()->route('families.vehicles.service-logs.index', ['family' => $family->id, 'vehicle' => $vehicle->id])
            ->with('success', 'Service log updated successfully.');
    }

    public function destroy(Request $request, Family $family, Vehicle $vehicle, ServiceLog $serviceLog): RedirectResponse
    {
        $this->authorize('view', $vehicle);

        $serviceLog->delete();

        return redirect()->route('families.vehicles.service-logs.index', ['family' => $family->id, 'vehicle' => $vehicle->id])
            ->with('success', 'Service log deleted successfully.');
    }

    private function getBudgetsForFamily(Family $family): \Illuminate\Support\Collection
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $userRole = \App\Models\FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->select('role')
            ->first();
        $isAdminOrOwner = $userRole && in_array($userRole->role, ['OWNER', 'ADMIN']);

        $budgetsQuery = Budget::where('family_id', $family->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->with(['category:id,name']);

        if ($isAdminOrOwner) {
            return $budgetsQuery->get();
        }

        $currentUserMember = FamilyMember::where('family_id', $family->id)
            ->where('user_id', Auth::id())
            ->select('id')
            ->first();

        return $budgetsQuery->where(function ($q) use ($currentUserMember) {
            $q->whereNull('family_member_id')
                ->orWhere('family_member_id', $currentUserMember?->id);
        })->get();
    }
}






















