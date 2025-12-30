<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Medicine extends Model
{
    use HasFactory;

    /**
     * Scope medicines to a specific family.
     */
    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'prescription_id',
        'name',
        'description',
        'manufacturer',
        'batch_number',
        'quantity',
        'unit',
        'min_stock_level',
        'expiry_date',
        'purchase_date',
        'purchase_price',
        'prescription_file_path',
        'prescription_original_name',
        'prescription_mime_type',
        'prescription_file_size',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'min_stock_level' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'expiry_date' => 'date',
            'purchase_date' => 'date',
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

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function expiryReminders(): HasMany
    {
        return $this->hasMany(MedicineExpiryReminder::class);
    }

    public function intakeReminders(): HasMany
    {
        return $this->hasMany(MedicineIntakeReminder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if medicine is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Check if medicine is low stock
     */
    public function isLowStock(): bool
    {
        if (!$this->min_stock_level) {
            return false;
        }

        return $this->quantity < $this->min_stock_level;
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return (int)now()->diffInDays($this->expiry_date, false);
    }
}






