<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Document extends Model
{
    use HasFactory;

    public const TYPES = [
        'AADHAAR',
        'PAN',
        'PASSPORT',
        'DRIVING_LICENSE',
        'PROPERTY',
        'INSURANCE',
        'CERTIFICATE',
        'OTHER',
    ];

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'uploaded_by',
        'title',
        'document_type',
        'is_sensitive',
        'password_hash',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'expires_at',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
            'expires_at' => 'date',
            'last_notified_at' => 'datetime',
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

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(DocumentReminder::class);
    }

    public function isPasswordProtected(): bool
    {
        return $this->is_sensitive && !empty($this->password_hash);
    }

    public function isLinkedMember(User $user): bool
    {
        return $this->familyMember?->user_id === $user->id;
    }

    public function requiresPasswordFor(User $user): bool
    {
        return $this->isPasswordProtected();
    }

    public function verifyPassword(string $password): bool
    {
        if (!$this->isPasswordProtected()) {
            return true;
        }

        return Hash::check($password, $this->password_hash);
    }

    protected function passwordHash(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? Hash::make($value) : null,
        );
    }
}

