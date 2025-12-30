<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'medical_record_id',
        'visit_date',
        'visit_time',
        'status',
        'doctor_name',
        'clinic_name',
        'specialization',
        'visit_type',
        'chief_complaint',
        'examination_findings',
        'diagnosis',
        'treatment_given',
        'notes',
        'follow_up_at',
        'next_visit_date',
        'reminder_sent_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'visit_time' => 'datetime:H:i',
            'follow_up_at' => 'date',
            'next_visit_date' => 'date',
            'reminder_sent_at' => 'datetime',
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

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
