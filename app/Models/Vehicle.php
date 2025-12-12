<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'make',
        'model',
        'year',
        'registration_number',
        'rc_expiry_date',
        'insurance_expiry_date',
        'puc_expiry_date',
        'color',
        'fuel_type',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'rc_expiry_date' => 'date',
            'insurance_expiry_date' => 'date',
            'puc_expiry_date' => 'date',
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

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class);
    }

    public function fuelEntries(): HasMany
    {
        return $this->hasMany(FuelEntry::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(VehicleReminder::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }

    /**
     * Calculate average mileage from fuel entries.
     */
    public function calculateAverageMileage(?int $limit = null): ?float
    {
        $entries = $this->fuelEntries()
            ->whereNotNull('calculated_mileage')
            ->orderBy('fill_date', 'desc')
            ->orderBy('id', 'desc');

        if ($limit) {
            $entries->limit($limit);
        }

        $entries = $entries->get();

        if ($entries->isEmpty()) {
            return null;
        }

        return (float) $entries->avg('calculated_mileage');
    }
}

