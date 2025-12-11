<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'title',
        'record_type',
        'doctor_name',
        'primary_condition',
        'severity',
        'symptoms',
        'diagnosis',
        'treatment_plan',
        'follow_up_at',
        'recorded_at',
        'summary',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'date',
            'follow_up_at' => 'date',
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

    public function visits(): HasMany
    {
        return $this->hasMany(DoctorVisit::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}

