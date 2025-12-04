<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRoleRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'user_id',
        'request_count',
        'last_requested_at',
        'status',
    ];

    protected $casts = [
        'last_requested_at' => 'datetime',
    ];

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
     * Check if user can request admin role again (2 days must have passed).
     */
    public function canRequestAgain(): bool
    {
        if (!$this->last_requested_at) {
            return true;
        }

        $nextRequestDate = $this->last_requested_at->copy()->addDays(2);
        return $nextRequestDate->isPast();
    }

    /**
     * Get the number of days until the next request is allowed.
     */
    public function getDaysUntilNextRequest(): int
    {
        if (!$this->last_requested_at) {
            return 0;
        }

        $nextRequestDate = $this->last_requested_at->copy()->addDays(2);
        if ($nextRequestDate->isPast()) {
            return 0;
        }

        return now()->diffInDays($nextRequestDate, false) + 1;
    }

    /**
     * Check if this request is eligible for auto-promotion (3+ requests).
     */
    public function isEligibleForAutoPromotion(): bool
    {
        return $this->status === 'pending' && $this->request_count >= 3;
    }
}
