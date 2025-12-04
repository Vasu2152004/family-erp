<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyUserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'user_id',
        'role',
        'is_backup_admin',
    ];

    protected function casts(): array
    {
        return [
            'is_backup_admin' => 'boolean',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'OWNER';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isMember(): bool
    {
        return $this->role === 'MEMBER';
    }

    public function isViewer(): bool
    {
        return $this->role === 'VIEWER';
    }

    public function canManageFamily(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }
}
