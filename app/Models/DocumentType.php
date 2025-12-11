<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'supports_expiry',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'supports_expiry' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($documentType) {
            if (empty($documentType->slug)) {
                $documentType->slug = Str::slug($documentType->name);
            }
        });
    }
}
