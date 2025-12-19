<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'title',
        'body',
        'visibility',
        'pin_hash',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->where('visibility', 'shared');
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('visibility', 'private');
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('visibility', 'locked');
    }

    public function isLocked(): bool
    {
        return $this->visibility === 'locked';
    }
}







