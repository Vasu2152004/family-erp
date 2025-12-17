<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'asset_type',
        'name',
        'description',
        'purchase_date',
        'purchase_value',
        'current_value',
        'location',
        'notes',
        'is_locked',
        'pin_hash',
        'encrypted_notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_value' => 'decimal:2',
            'current_value' => 'decimal:2',
            'is_locked' => 'boolean',
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

    /**
     * Scope to filter assets by tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter assets by family.
     */
    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }

    /**
     * Scope to filter assets by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('asset_type', $type);
    }

    public function isLocked(): bool
    {
        return $this->is_locked === true;
    }

    public function unlockAccesses(): HasMany
    {
        return $this->hasMany(AssetUnlockAccess::class);
    }

    public function isUnlockedFor(User $user): bool
    {
        return $this->unlockAccesses()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function setPinHash(?string $pin): void
    {
        $this->pin_hash = $pin ? Hash::make($pin) : null;
    }

    public function verifyPin(?string $pin): bool
    {
        if (empty($this->pin_hash) || $pin === null) {
            return false;
        }

        return Hash::check($pin, $this->pin_hash);
    }

    public function encryptNotes(): void
    {
        if (!empty($this->notes)) {
            $this->encrypted_notes = Crypt::encryptString($this->notes);
            $this->notes = null;
        }
    }

    public function decryptNotes(): void
    {
        if (!empty($this->encrypted_notes)) {
            try {
                $this->notes = Crypt::decryptString($this->encrypted_notes);
            } catch (\Exception $e) {
                $this->notes = null;
            }
        }
    }

    public function creatorFamilyMember(): ?FamilyMember
    {
        $creatorMember = $this->createdBy?->familyMember;

        if ($creatorMember && $creatorMember->family_id === $this->family_id) {
            return $creatorMember;
        }

        return null;
    }

    public function effectiveOwnerFamilyMember(): ?FamilyMember
    {
        return $this->familyMember ?? $this->creatorFamilyMember();
    }

    public function getOwnerNameAttribute(): ?string
    {
        if ($this->familyMember) {
            return trim("{$this->familyMember->first_name} {$this->familyMember->last_name}");
        }

        return $this->createdBy?->name;
    }

    public function effectiveOwnerUserId(): ?int
    {
        $owner = $this->effectiveOwnerFamilyMember();

        if ($owner && $owner->user_id) {
            return $owner->user_id;
        }

        if ($this->family_member_id === null) {
            return $this->created_by;
        }

        return null;
    }

    public function isEffectiveOwner(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->effectiveOwnerUserId() === $user->id;
    }

    public function isOwnerDeceased(): bool
    {
        $owner = $this->effectiveOwnerFamilyMember();

        return $owner?->is_deceased === true;
    }

    public function canBeRequestedForUnlock(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (!$this->is_locked) {
            return false;
        }

        if (!$this->isOwnerDeceased()) {
            return false;
        }

        $role = $user->getFamilyRole($this->family_id);

        return $role && in_array($role->role, ['OWNER', 'ADMIN']);
    }
}
