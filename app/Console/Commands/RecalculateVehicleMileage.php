<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Console\Command;

class RecalculateVehicleMileage extends Command
{
    protected $signature = 'vehicles:recalculate-mileage {vehicle_id?}';
    protected $description = 'Recalculate mileage for vehicle fuel entries';

    public function handle(VehicleService $vehicleService): int
    {
        $vehicleId = $this->argument('vehicle_id');

        if ($vehicleId) {
            $vehicle = Vehicle::find($vehicleId);
            if (!$vehicle) {
                $this->error("Vehicle with ID {$vehicleId} not found.");
                return 1;
            }
            $vehicles = collect([$vehicle]);
        } else {
            $vehicles = Vehicle::all();
        }

        $this->info("Recalculating mileage for " . $vehicles->count() . " vehicle(s)...");

        foreach ($vehicles as $vehicle) {
            $this->line("Processing: {$vehicle->make} {$vehicle->model} (ID: {$vehicle->id})");
            
            $entries = $vehicle->fuelEntries()->orderBy('fill_date', 'asc')->get();
            
            if ($entries->isEmpty()) {
                $this->warn("  No fuel entries found.");
                continue;
            }

            $this->info("  Found {$entries->count()} fuel entries:");
            
            foreach ($entries as $entry) {
                $this->line("    - {$entry->fill_date->format('Y-m-d')}: {$entry->odometer_reading} km, {$entry->fuel_amount}L");
            }

            $vehicleService->recalculateAllMileages($vehicle);

            $updatedEntries = $vehicle->fuelEntries()->whereNotNull('calculated_mileage')->get();
            $this->info("  Calculated mileage for {$updatedEntries->count()} entries:");
            
            foreach ($updatedEntries as $entry) {
                $this->line("    - {$entry->fill_date->format('Y-m-d')}: {$entry->calculated_mileage} km/l");
            }

            $averageMileage = $vehicle->calculateAverageMileage();
            if ($averageMileage) {
                $this->info("  Average Mileage: " . number_format($averageMileage, 2) . " km/l");
            } else {
                $this->warn("  Average Mileage: Not enough data");
            }
        }

        $this->info("Done!");
        return 0;
    }
}















