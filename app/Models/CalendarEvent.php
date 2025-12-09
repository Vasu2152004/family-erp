<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'reminder_before_minutes',
        'reminder_sent_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'reminder_before_minutes' => 'integer',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }

    public function shouldSendReminder(): bool
    {
        if (!$this->reminder_before_minutes || $this->reminder_sent_at) {
            return false;
        }

        $reminderTime = $this->start_at->copy()->subMinutes($this->reminder_before_minutes);

        return Carbon::now()->greaterThanOrEqualTo($reminderTime)
            && Carbon::now()->lessThanOrEqualTo($this->start_at);
    }
}

