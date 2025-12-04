<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'relation',
        'phone',
        'email',
        'is_deceased',
        'date_of_death',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_death' => 'date',
            'is_deceased' => 'boolean',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter members by tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get only alive members.
     */
    public function scopeAlive(Builder $query): Builder
    {
        return $query->where('is_deceased', false);
    }

    /**
     * Scope to get only deceased members.
     */
    public function scopeDeceased(Builder $query): Builder
    {
        return $query->where('is_deceased', true);
    }
}
