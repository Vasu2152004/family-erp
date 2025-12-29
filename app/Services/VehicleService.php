<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vehicle;
use App\Models\ServiceLog;
use App\Models\FuelEntry;
use App\Models\VehicleReminder;
use App\Models\VehicleSale;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\FinanceAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    /**
     * Create a vehicle.
     */
    public function createVehicle(array $data, int $tenantId, int $familyId, int $userId): Vehicle
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $userId) {
            $vehicle = Vehicle::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $data['family_member_id'] ?? null,
                'make' => $data['make'],
                'model' => $data['model'],
                'year' => $data['year'],
                'registration_number' => $data['registration_number'],
                'rc_expiry_date' => $data['rc_expiry_date'] ?? null,
                'insurance_expiry_date' => $data['insurance_expiry_date'] ?? null,
                'puc_expiry_date' => $data['puc_expiry_date'] ?? null,
                'color' => $data['color'] ?? null,
                'fuel_type' => $data['fuel_type'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->syncReminders($vehicle);

            return $vehicle;
        });
    }

    /**
     * Update a vehicle.
     */
    public function updateVehicle(Vehicle $vehicle, array $data, int $userId): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $data, $userId) {
            $vehicle->update([
                'family_member_id' => $data['family_member_id'] ?? $vehicle->family_member_id,
                'make' => $data['make'] ?? $vehicle->make,
                'model' => $data['model'] ?? $vehicle->model,
                'year' => $data['year'] ?? $vehicle->year,
                'registration_number' => $data['registration_number'] ?? $vehicle->registration_number,
                'rc_expiry_date' => $data['rc_expiry_date'] ?? $vehicle->rc_expiry_date,
                'insurance_expiry_date' => $data['insurance_expiry_date'] ?? $vehicle->insurance_expiry_date,
                'puc_expiry_date' => $data['puc_expiry_date'] ?? $vehicle->puc_expiry_date,
                'color' => $data['color'] ?? $vehicle->color,
                'fuel_type' => $data['fuel_type'] ?? $vehicle->fuel_type,
                'updated_by' => $userId,
            ]);

            $this->syncReminders($vehicle);

            return $vehicle->fresh();
        });
    }

    /**
     * Delete a vehicle and related data.
     * If sale data is provided, creates a vehicle sale record and income transaction.
     */
    public function deleteVehicle(Vehicle $vehicle, ?array $saleData = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($vehicle, $saleData, $userId) {
            // If vehicle is sold, create sale record and transaction
            if ($saleData && isset($saleData['is_sold']) && $saleData['is_sold']) {
                $this->createVehicleSale($vehicle, $saleData, $userId);
            }

            // Delete related data
            $vehicle->reminders()->delete();
            $vehicle->serviceLogs()->delete();
            $vehicle->fuelEntries()->delete();
            $vehicle->delete();
        });
    }

    /**
     * Create vehicle sale record and income transaction.
     */
    private function createVehicleSale(Vehicle $vehicle, array $saleData, ?int $userId): void
    {
        // Create vehicle sale record
        $vehicleSale = VehicleSale::create([
            'tenant_id' => $vehicle->tenant_id,
            'family_id' => $vehicle->family_id,
            'vehicle_id' => $vehicle->id,
            'sold_to' => $saleData['sold_to'],
            'sold_date' => $saleData['sold_date'],
            'sold_price' => $saleData['sold_price'],
            'notes' => $saleData['notes'] ?? null,
            'created_by' => $userId ?? auth()->id(),
        ]);

        // Get or create vehicle sale category
        $category = TransactionCategory::firstOrCreate(
            [
                'tenant_id' => $vehicle->tenant_id,
                'family_id' => $vehicle->family_id,
                'name' => 'Vehicle Sale',
                'type' => 'INCOME',
            ],
            [
                'is_system' => false,
                'icon' => '🚗',
                'color' => '#10b981',
            ]
        );

        // Get default finance account (first active account or create one)
        $financeAccount = FinanceAccount::where('family_id', $vehicle->family_id)
            ->where('is_active', true)
            ->first();

        if (!$financeAccount) {
            // Create a default cash account if none exists
            $financeAccount = FinanceAccount::create([
                'tenant_id' => $vehicle->tenant_id,
                'family_id' => $vehicle->family_id,
                'name' => 'Cash',
                'type' => 'CASH',
                'initial_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]);
        }

        // Create income transaction
        // Transaction should be on vehicle owner if exists, otherwise family transaction (family_member_id = null)
        Transaction::create([
            'tenant_id' => $vehicle->tenant_id,
            'family_id' => $vehicle->family_id,
            'finance_account_id' => $financeAccount->id,
            'family_member_id' => $vehicle->family_member_id, // Vehicle owner if exists, null for family transaction
            'category_id' => $category->id,
            'type' => 'INCOME',
            'amount' => $saleData['sold_price'],
            'description' => "Vehicle Sale: {$vehicle->make} {$vehicle->model} ({$vehicle->registration_number}) - Sold to {$saleData['sold_to']}",
            'transaction_date' => $saleData['sold_date'],
            'is_shared' => $vehicle->family_member_id ? false : true, // Shared if no owner, private if has owner
        ]);

        // Update account balance
        $financeAccount->updateBalance();
    }

    /**
     * Create a service log.
     */
    public function createServiceLog(array $data, int $tenantId, int $familyId, Vehicle $vehicle, int $userId): ServiceLog
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $vehicle, $userId) {
            return ServiceLog::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'vehicle_id' => $vehicle->id,
                'service_date' => $data['service_date'],
                'odometer_reading' => $data['odometer_reading'],
                'cost' => $data['cost'],
                'service_center_name' => $data['service_center_name'] ?? null,
                'service_center_contact' => $data['service_center_contact'] ?? null,
                'service_type' => $data['service_type'],
                'description' => $data['description'] ?? null,
                'next_service_due_date' => $data['next_service_due_date'] ?? null,
                'next_service_odometer' => $data['next_service_odometer'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Update a service log.
     */
    public function updateServiceLog(ServiceLog $serviceLog, array $data, int $userId): ServiceLog
    {
        return DB::transaction(function () use ($serviceLog, $data, $userId) {
            $serviceLog->update([
                'service_date' => $data['service_date'] ?? $serviceLog->service_date,
                'odometer_reading' => $data['odometer_reading'] ?? $serviceLog->odometer_reading,
                'cost' => $data['cost'] ?? $serviceLog->cost,
                'service_center_name' => $data['service_center_name'] ?? $serviceLog->service_center_name,
                'service_center_contact' => $data['service_center_contact'] ?? $serviceLog->service_center_contact,
                'service_type' => $data['service_type'] ?? $serviceLog->service_type,
                'description' => $data['description'] ?? $serviceLog->description,
                'next_service_due_date' => $data['next_service_due_date'] ?? $serviceLog->next_service_due_date,
                'next_service_odometer' => $data['next_service_odometer'] ?? $serviceLog->next_service_odometer,
                'updated_by' => $userId,
            ]);

            return $serviceLog->fresh();
        });
    }

    /**
     * Create a fuel entry and calculate mileage.
     */
    public function createFuelEntry(array $data, int $tenantId, int $familyId, Vehicle $vehicle, int $userId): FuelEntry
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $vehicle, $userId) {
            $calculatedMileage = $this->calculateMileageWithFuel(
                $vehicle,
                (int) $data['odometer_reading'],
                (float) $data['fuel_amount'],
                $data['fill_date']
            );

            $fuelEntry = FuelEntry::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'vehicle_id' => $vehicle->id,
                'fill_date' => $data['fill_date'],
                'odometer_reading' => $data['odometer_reading'],
                'fuel_amount' => $data['fuel_amount'],
                'cost' => $data['cost'],
                'fuel_type' => $data['fuel_type'],
                'fuel_station_name' => $data['fuel_station_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'calculated_mileage' => $calculatedMileage,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Recalculate mileage for subsequent entries if needed
            $this->recalculateSubsequentMileages($vehicle, $fuelEntry);

            return $fuelEntry;
        });
    }

    /**
     * Update a fuel entry and recalculate mileage.
     */
    public function updateFuelEntry(FuelEntry $fuelEntry, array $data, int $userId): FuelEntry
    {
        return DB::transaction(function () use ($fuelEntry, $data, $userId) {
            $oldOdometer = $fuelEntry->odometer_reading;
            $oldDate = $fuelEntry->fill_date;

            $fuelEntry->update([
                'fill_date' => $data['fill_date'] ?? $fuelEntry->fill_date,
                'odometer_reading' => $data['odometer_reading'] ?? $fuelEntry->odometer_reading,
                'fuel_amount' => $data['fuel_amount'] ?? $fuelEntry->fuel_amount,
                'cost' => $data['cost'] ?? $fuelEntry->cost,
                'fuel_type' => $data['fuel_type'] ?? $fuelEntry->fuel_type,
                'fuel_station_name' => $data['fuel_station_name'] ?? $fuelEntry->fuel_station_name,
                'notes' => $data['notes'] ?? $fuelEntry->notes,
                'updated_by' => $userId,
            ]);

            // Recalculate mileage for this entry and subsequent ones
            $vehicle = $fuelEntry->vehicle;
            $fillDate = $fuelEntry->fill_date instanceof \Carbon\Carbon ? $fuelEntry->fill_date->toDateString() : (string) $fuelEntry->fill_date;
            $calculatedMileage = $this->calculateMileageWithFuel(
                $vehicle,
                (int) $fuelEntry->odometer_reading,
                (float) $fuelEntry->fuel_amount,
                $fillDate,
                $fuelEntry->id
            );
            $fuelEntry->update(['calculated_mileage' => $calculatedMileage]);

            $this->recalculateSubsequentMileages($vehicle, $fuelEntry);

            return $fuelEntry->fresh();
        });
    }


    /**
     * Calculate mileage with fuel amount.
     */
    public function calculateMileageWithFuel(Vehicle $vehicle, int|string $currentOdometer, float|string $fuelAmount, string|\Carbon\Carbon $fillDate, ?int $excludeEntryId = null): ?float
    {
        // Cast to proper types
        $currentOdometer = (int) $currentOdometer;
        $fuelAmount = (float) $fuelAmount;
        $fillDate = $fillDate instanceof \Carbon\Carbon ? $fillDate->toDateString() : (string) $fillDate;
        
        if ($fuelAmount <= 0) {
            return null;
        }

        // Find the most recent entry before this one (by date, then by ID)
        $query = $vehicle->fuelEntries()
            ->where(function ($query) use ($fillDate, $excludeEntryId) {
                $query->where('fill_date', '<', $fillDate);
                if ($excludeEntryId) {
                    $query->where('id', '!=', $excludeEntryId);
                }
            })
            ->orderBy('fill_date', 'desc')
            ->orderBy('id', 'desc');

        $previousEntry = $query->first();

        // First entry has no previous entry to compare with
        if (!$previousEntry || !$previousEntry->odometer_reading) {
            return null;
        }

        // Calculate distance traveled
        $distance = $currentOdometer - $previousEntry->odometer_reading;
        
        // Distance must be positive (odometer should increase over time)
        if ($distance <= 0) {
            // Odometer reading decreased or stayed same - invalid data
            return null;
        }

        // Calculate mileage: distance (km) / fuel (liters) = km/l
        return round($distance / $fuelAmount, 2);
    }

    /**
     * Recalculate mileage for subsequent fuel entries.
     */
    public function recalculateSubsequentMileages(Vehicle $vehicle, FuelEntry $currentEntry): void
    {
        $subsequentEntries = $vehicle->fuelEntries()
            ->where(function ($query) use ($currentEntry) {
                $query->where('fill_date', '>', $currentEntry->fill_date)
                    ->orWhere(function ($q) use ($currentEntry) {
                        $q->where('fill_date', $currentEntry->fill_date)
                            ->where('id', '>', $currentEntry->id);
                    });
            })
            ->orderBy('fill_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($subsequentEntries as $entry) {
            $mileage = $this->calculateMileageWithFuel(
                $vehicle,
                (int) $entry->odometer_reading,
                (float) $entry->fuel_amount,
                $entry->fill_date instanceof \Carbon\Carbon ? $entry->fill_date->toDateString() : (string) $entry->fill_date,
                $entry->id
            );
            $entry->update(['calculated_mileage' => $mileage]);
        }
    }

    /**
     * Recalculate mileage for all fuel entries of a vehicle.
     * This is useful when entries were created before mileage calculation was implemented.
     */
    public function recalculateAllMileages(Vehicle $vehicle): void
    {
        $entries = $vehicle->fuelEntries()
            ->orderBy('fill_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($entries as $entry) {
            $mileage = $this->calculateMileageWithFuel(
                $vehicle,
                (int) $entry->odometer_reading,
                (float) $entry->fuel_amount,
                $entry->fill_date instanceof \Carbon\Carbon ? $entry->fill_date->toDateString() : (string) $entry->fill_date,
                $entry->id
            );
            $entry->update(['calculated_mileage' => $mileage]);
        }
    }

    /**
     * Sync reminders for vehicle expiry dates.
     */
    private function syncReminders(Vehicle $vehicle): void
    {
        $reminderTypes = [
            'rc_expiry' => $vehicle->rc_expiry_date,
            'insurance_expiry' => $vehicle->insurance_expiry_date,
            'puc_expiry' => $vehicle->puc_expiry_date,
        ];

        foreach ($reminderTypes as $type => $expiryDate) {
            if (!$expiryDate) {
                $vehicle->reminders()->where('reminder_type', $type)->delete();
                continue;
            }

            $expiry = CarbonImmutable::parse($expiryDate);
            $dates = collect([
                $expiry->subDays(30),
                $expiry->subDays(7),
                $expiry,
            ])->filter(fn (CarbonImmutable $date) => $date->isToday() || $date->isFuture())
                ->map(fn (CarbonImmutable $date) => $date->toDateString());

            foreach ($dates as $date) {
                VehicleReminder::updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'reminder_type' => $type,
                        'remind_at' => $date,
                    ],
                    [
                        'tenant_id' => $vehicle->tenant_id,
                        'family_id' => $vehicle->family_id,
                    ]
                );
            }

            $vehicle->reminders()
                ->where('reminder_type', $type)
                ->whereNotIn('remind_at', $dates)
                ->delete();
        }
    }

    /**
     * Calculate average mileage from fuel entries.
     */
    public function calculateAverageMileage(Vehicle $vehicle, ?int $limit = null): ?float
    {
        return $vehicle->calculateAverageMileage($limit);
    }
}

