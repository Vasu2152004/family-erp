<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function familyMember(): HasOne
    {
        return $this->hasOne(FamilyMember::class);
    }

    public function familyRoles(): HasMany
    {
        return $this->hasMany(FamilyUserRole::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Get user's role for a specific family (cached).
     */
    public function getFamilyRole(int $familyId): ?FamilyUserRole
    {
        return Cache::remember(
            "user_role_{$this->id}_{$familyId}",
            now()->addHours(1),
            fn () => $this->familyRoles()->where('family_id', $familyId)->first()
        );
    }

    /**
     * Check if user has a specific role for a family (cached).
     */
    public function hasFamilyRole(int $familyId, string $role): bool
    {
        $userRole = $this->getFamilyRole($familyId);
        return $userRole && $userRole->role === $role;
    }

    /**
     * Check if user is the owner of a family (cached).
     */
    public function isFamilyOwner(int $familyId): bool
    {
        return $this->hasFamilyRole($familyId, 'OWNER');
    }

    /**
     * Check if user is an admin of a family (cached).
     */
    public function isFamilyAdmin(int $familyId): bool
    {
        return $this->hasFamilyRole($familyId, 'ADMIN') || $this->isFamilyOwner($familyId);
    }
}
