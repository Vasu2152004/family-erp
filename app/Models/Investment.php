<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'investment_type',
        'name',
        'description',
        'amount',
        'current_value',
        'is_hidden',
        'pin_hash',
        'encrypted_details',
        'details',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'current_value' => 'decimal:2',
            'is_hidden' => 'boolean',
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

    public function unlockRequests(): HasMany
    {
        return $this->hasMany(InvestmentUnlockRequest::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(InvestmentAccessLog::class);
    }

    public function unlockAccess(): HasMany
    {
        return $this->hasMany(InvestmentUnlockAccess::class);
    }

    /**
     * Scope to filter investments by tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter investments by family.
     */
    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }

    /**
     * Scope to get only hidden investments.
     */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('is_hidden', true);
    }

    /**
     * Scope to get only visible investments.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Check if investment is hidden.
     */
    public function isHidden(): bool
    {
        return $this->is_hidden === true;
    }

    /**
     * Check if investment requires PIN.
     */
    public function requiresPin(): bool
    {
        return $this->is_hidden && !empty($this->pin_hash);
    }

    /**
     * Check if investment is unlocked for user.
     */
    public function isUnlockedFor(User $user): bool
    {
        return InvestmentUnlockAccess::where('investment_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Get unlock requests for specific user.
     */
    public function getUnlockRequestsFor(User $user)
    {
        return $this->unlockRequests()
            ->where('requested_by', $user->id)
            ->get();
    }

    /**
     * Encrypt investment details when hiding.
     */
    public function encryptDetails(): void
    {
        if (!empty($this->details)) {
            $this->encrypted_details = Crypt::encryptString($this->details);
            $this->details = null;
        }
    }

    /**
     * Decrypt investment details when unlocking.
     */
    public function decryptDetails(): void
    {
        if (!empty($this->encrypted_details)) {
            try {
                $this->details = Crypt::decryptString($this->encrypted_details);
                // Keep encrypted_details in DB, only decrypt on-the-fly for display
            } catch (\Exception $e) {
                // Handle decryption error
                $this->details = null;
            }
        }
    }

    /**
     * Verify PIN.
     */
    public function verifyPin(string $pin): bool
    {
        if (empty($this->pin_hash)) {
            return false;
        }

        return Hash::check($pin, $this->pin_hash);
    }

    /**
     * Set PIN hash.
     */
    public function setPinHash(?string $pin): void
    {
        if ($pin) {
            $this->pin_hash = Hash::make($pin);
        } else {
            $this->pin_hash = null;
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

    /**
     * Check if investment owner is deceased.
     */
    public function isOwnerDeceased(): bool
    {
        $owner = $this->effectiveOwnerFamilyMember();

        return $owner?->is_deceased === true;
    }

    /**
     * Check if investment can be requested for unlock by user.
     */
    public function canBeRequestedForUnlock(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (!$this->is_hidden) {
            return false;
        }

        if (!$this->isOwnerDeceased()) {
            return false;
        }

        $role = $user->getFamilyRole($this->family_id);

        return $role && in_array($role->role, ['OWNER', 'ADMIN']);
    }
}
