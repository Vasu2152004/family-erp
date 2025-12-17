<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentUnlockAccess extends Model
{
    use HasFactory;

    protected $table = 'investment_unlock_access';

    protected $fillable = [
        'investment_id',
        'user_id',
        'unlocked_at',
        'unlocked_via',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(InvestmentUnlockRequest::class, 'request_id');
    }
}
