<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetUnlockRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'asset_id',
        'requested_by',
        'request_count',
        'last_requested_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'last_requested_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}








