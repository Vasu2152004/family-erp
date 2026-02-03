<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Vehicles\StoreFuelEntryRequest;
use App\Http\Requests\Vehicles\UpdateFuelEntryRequest;
use App\Models\Family;
use App\Models\FinanceAccount;
use App\Models\Vehicle;
use App\Models\FuelEntry;
use App\Models\Budget;
use App\Models\FamilyMember;
use App\Services\VehicleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuelEntryController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService
    ) {
    }

    public function index(Request $request, Family $family, Vehicle $vehicle): View
    {
        $this->authorize('view', $vehicle);

        $query = FuelEntry::where('vehicle_id', $vehicle->id)
            ->where('tenant_id', $family->tenant_id)
            ->where('family_id', $family->id)
            ->with(['createdBy', 'updatedBy']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('fuel_station_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $query->latestFirst();

        // Always recalculate mileage to ensure it's up to date
        $this->vehicleService->recalculateAllMileages($vehicle);
        
        $fuelEntries = $query->simplePaginate(10)->appends($request->query());

        $averageMileage = $vehicle->calculateAverageMileage();

        return view('vehicles.fuel-entries.index', [
            'family' => $family,
            'vehicle' => $vehicle,
            'fuelEntries' => $fuelEntries,
            'averageMileage' => $averageMileage,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request, Family $family, Vehicle $vehicle): View
    {
        $this->authorize('view', $vehicle);

        $budgets = $this->getCachedBudgetsForFamily($family);
        $accounts = $this->getCachedActiveAccounts($family->id);

        return view('vehicles.fuel-entries.create', [
            'family' => $family,
            'vehicle' => $vehicle,
            'budgets' => $budgets,
            'accounts' => $accounts,
        ]);
    }

    public function store(StoreFuelEntryRequest $request, Family $family, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('view', $vehicle);

        $fuelEntry = $this->vehicleService->createFuelEntry(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $vehicle,
            $request->user()->id
        );

        return redirect()->route('families.vehicles.fuel-entries.index', ['family' => $family->id, 'vehicle' => $vehicle->id])
            ->with('success', 'Fuel entry created successfully.');
    }

    public function edit(Request $request, Family $family, Vehicle $vehicle, FuelEntry $fuelEntry): View
    {
        $this->authorize('view', $vehicle);

        $budgets = $this->getCachedBudgetsForFamily($family);
        $accounts = $this->getCachedActiveAccounts($family->id);

        return view('vehicles.fuel-entries.edit', [
            'family' => $family,
            'vehicle' => $vehicle,
            'fuelEntry' => $fuelEntry,
            'budgets' => $budgets,
            'accounts' => $accounts,
        ]);
    }

    public function update(UpdateFuelEntryRequest $request, Family $family, Vehicle $vehicle, FuelEntry $fuelEntry): RedirectResponse
    {
        $this->authorize('view', $vehicle);

        $this->vehicleService->updateFuelEntry(
            $fuelEntry,
            $request->validated(),
            $request->user()->id
        );

        return redirect()->route('families.vehicles.fuel-entries.index', ['family' => $family->id, 'vehicle' => $vehicle->id])
            ->with('success', 'Fuel entry updated successfully.');
    }

    public function destroy(Request $request, Family $family, Vehicle $vehicle, FuelEntry $fuelEntry): RedirectResponse
    {
        $this->authorize('view', $vehicle);

        $fillDate = $fuelEntry->fill_date;
        $entryId = $fuelEntry->id;

        $fuelEntry->delete();

        // Recalculate subsequent entries after deletion
        $subsequentEntries = FuelEntry::where('vehicle_id', $vehicle->id)
            ->where(function ($query) use ($fillDate, $entryId) {
                $query->where('fill_date', '>', $fillDate)
                    ->orWhere(function ($q) use ($fillDate, $entryId) {
                        $q->where('fill_date', $fillDate)
                            ->where('id', '>', $entryId);
                    });
            })
            ->orderBy('fill_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($subsequentEntries as $entry) {
            $fillDate = $entry->fill_date instanceof \Carbon\Carbon ? $entry->fill_date->toDateString() : (string) $entry->fill_date;
            $mileage = $this->vehicleService->calculateMileageWithFuel(
                $vehicle,
                (int) $entry->odometer_reading,
                (float) $entry->fuel_amount,
                $fillDate,
                $entry->id
            );
            $entry->update(['calculated_mileage' => $mileage]);
        }

        return redirect()->route('families.vehicles.fuel-entries.index', ['family' => $family->id, 'vehicle' => $vehicle->id])
            ->with('success', 'Fuel entry deleted successfully.');
    }

    private function getCachedActiveAccounts(int $familyId): \Illuminate\Support\Collection
    {
        $key = 'vehicle_accounts_' . $familyId;
        if (!app()->bound($key)) {
            app()->instance($key, FinanceAccount::where('family_id', $familyId)->where('is_active', true)->get());
        }
        return app($key);
    }

    private function getCachedBudgetsForFamily(Family $family): \Illuminate\Support\Collection
    {
        $userId = Auth::id();
        $key = 'vehicle_budgets_' . $family->id . '_' . $userId;
        if (!app()->bound($key)) {
            app()->instance($key, $this->getBudgetsForFamily($family));
        }
        return app($key);
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

