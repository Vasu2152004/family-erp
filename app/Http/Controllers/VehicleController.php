<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Vehicles\StoreVehicleRequest;
use App\Http\Requests\Vehicles\UpdateVehicleRequest;
use App\Models\Family;
use App\Models\Vehicle;
use App\Models\FamilyMember;
use App\Services\VehicleService;
use App\Services\VehicleAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService,
        private readonly VehicleAnalyticsService $analyticsService
    ) {
    }

    public function index(Request $request, Family $family): View
    {
        $user = $request->user();
        
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('viewAny', Vehicle::class);

        $query = Vehicle::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->with(['familyMember', 'createdBy']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('family_member_id')) {
            $query->where('family_member_id', $request->integer('family_member_id'));
        }

        if ($request->filled('expiring_soon')) {
            $query->where(function ($q) {
                $q->where('rc_expiry_date', '<=', Carbon::today()->addDays(30))
                    ->orWhere('insurance_expiry_date', '<=', Carbon::today()->addDays(30))
                    ->orWhere('puc_expiry_date', '<=', Carbon::today()->addDays(30));
            });
        }

        $query->orderBy('created_at', 'desc');

        $vehicles = $query->paginate(10)->appends($request->query());

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        // Get analytics data for charts
        $fuelConsumptionData = $this->analyticsService->getFuelConsumptionTrends($family->id, 12);

        return view('vehicles.index', [
            'family' => $family,
            'vehicles' => $vehicles,
            'members' => $members,
            'filters' => $request->only(['search', 'family_member_id', 'expiring_soon']),
            'fuelConsumptionData' => $fuelConsumptionData,
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

        $this->authorize('create', Vehicle::class);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('vehicles.create', [
            'family' => $family,
            'members' => $members,
            'vehicle' => null,
        ]);
    }

    public function store(StoreVehicleRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('create', Vehicle::class);

        $vehicle = $this->vehicleService->createVehicle(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $request->user()->id
        );

        return redirect()->route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])
            ->with('success', 'Vehicle created successfully.');
    }

    public function show(Request $request, Family $family, Vehicle $vehicle): View
    {
        $user = $request->user();
        
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('view', $vehicle);

        // Always recalculate mileage to ensure it's up to date
        // This handles cases where entries were created before calculation was implemented
        $this->vehicleService->recalculateAllMileages($vehicle);

        $vehicle->load([
            'familyMember',
            'createdBy',
            'updatedBy',
            'serviceLogs' => fn ($q) => $q->latestFirst()->limit(3),
            'fuelEntries' => fn ($q) => $q->latestFirst()->limit(3),
        ]);

        $averageMileage = $vehicle->calculateAverageMileage();

        return view('vehicles.show', [
            'family' => $family,
            'vehicle' => $vehicle,
            'averageMileage' => $averageMileage,
        ]);
    }

    public function edit(Request $request, Family $family, Vehicle $vehicle): View
    {
        $user = $request->user();
        
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('update', $vehicle);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('vehicles.edit', [
            'family' => $family,
            'vehicle' => $vehicle,
            'members' => $members,
        ]);
    }

    public function update(UpdateVehicleRequest $request, Family $family, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $this->vehicleService->updateVehicle(
            $vehicle,
            $request->validated(),
            $request->user()->id
        );

        return redirect()->route('families.vehicles.show', ['family' => $family->id, 'vehicle' => $vehicle->id])
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Request $request, Family $family, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        $this->vehicleService->deleteVehicle($vehicle);

        return redirect()->route('families.vehicles.index', ['family' => $family->id])
            ->with('success', 'Vehicle deleted successfully.');
    }
}

