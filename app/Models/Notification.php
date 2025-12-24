<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Notification $notification): void {
            // Auto-fill tenant_id from the user when missing to satisfy non-null constraint.
            if (!$notification->tenant_id && $notification->user_id) {
                $user = Cache::remember(
                    'notification_user_' . $notification->user_id,
                    30,
                    fn() => User::find($notification->user_id)
                );

                if ($user) {
                    $notification->tenant_id = $user->tenant_id;
                }
            }

            // Hydrate title/message from data payload when missing to satisfy schema.
            if (!$notification->title || !$notification->message) {
                $dataPayload = $notification->data ?? null;
                $decoded = is_string($dataPayload) ? json_decode($dataPayload, true) : $dataPayload;

                if (is_array($decoded)) {
                    if (!$notification->title && isset($decoded['title'])) {
                        $notification->title = $decoded['title'];
                    }
                    if (!$notification->message && isset($decoded['message'])) {
                        $notification->message = $decoded['message'];
                    }
                }
            }
        });
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}















