<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'vehicle_id',
        'sold_to',
        'sold_date',
        'sold_price',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sold_date' => 'date',
            'sold_price' => 'decimal:2',
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
