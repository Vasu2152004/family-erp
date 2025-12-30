<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class FuelEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'vehicle_id',
        'fill_date',
        'odometer_reading',
        'fuel_amount',
        'cost',
        'fuel_type',
        'fuel_station_name',
        'notes',
        'calculated_mileage',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fill_date' => 'date',
            'odometer_reading' => 'integer',
            'fuel_amount' => 'decimal:2',
            'cost' => 'decimal:2',
            'calculated_mileage' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('fill_date', 'desc')->orderBy('id', 'desc');
    }

    public function scopeForVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeOrderedByDate(Builder $query): Builder
    {
        return $query->orderBy('fill_date', 'asc')->orderBy('id', 'asc');
    }
}














