<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Vehicles\StoreServiceLogRequest;
use App\Http\Requests\Vehicles\UpdateServiceLogRequest;
use App\Models\Family;
use App\Models\Vehicle;
use App\Models\ServiceLog;
use App\Services\VehicleService;
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

        $serviceLogs = $query->paginate(10)->appends($request->query());

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

        return view('vehicles.service-logs.create', [
            'family' => $family,
            'vehicle' => $vehicle,
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

        return view('vehicles.service-logs.edit', [
            'family' => $family,
            'vehicle' => $vehicle,
            'serviceLog' => $serviceLog,
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
}














