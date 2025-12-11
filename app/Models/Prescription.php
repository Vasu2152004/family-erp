<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'doctor_visit_id',
        'medication_name',
        'dosage',
        'frequency',
        'start_date',
        'end_date',
        'status',
        'instructions',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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

    public function doctorVisit(): BelongsTo
    {
        return $this->belongsTo(DoctorVisit::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(MedicineReminder::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}

