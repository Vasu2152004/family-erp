<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineExpiryReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'medicine_id',
        'remind_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'remind_at' => 'date',
            'sent_at' => 'datetime',
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

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}














