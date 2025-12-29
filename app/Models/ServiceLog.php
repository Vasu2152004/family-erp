<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ServiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'vehicle_id',
        'service_date',
        'odometer_reading',
        'cost',
        'service_center_name',
        'service_center_contact',
        'service_type',
        'description',
        'next_service_due_date',
        'next_service_odometer',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'odometer_reading' => 'integer',
            'cost' => 'decimal:2',
            'next_service_due_date' => 'date',
            'next_service_odometer' => 'integer',
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
        return $query->orderBy('service_date', 'desc')->orderBy('id', 'desc');
    }

    public function scopeForVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }
}













