<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'title',
        'description',
        'frequency',
        'family_member_id',
        'status',
        'due_date',
        'recurrence_day',
        'recurrence_time',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'recurrence_time' => 'datetime',
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

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    /**
     * Scope to filter tasks by tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter tasks by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter tasks by frequency.
     */
    public function scopeByFrequency(Builder $query, string $frequency): Builder
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Scope to filter tasks assigned to a specific member.
     */
    public function scopeAssignedTo(Builder $query, int $familyMemberId): Builder
    {
        return $query->where('family_member_id', $familyMemberId);
    }

    /**
     * Check if task can transition to a new status.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['in_progress'],
            'in_progress' => ['done', 'pending'],
            'done' => ['pending', 'in_progress'],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'in_progress' => 'blue',
            'done' => 'green',
            default => 'gray',
        };
    }
}
