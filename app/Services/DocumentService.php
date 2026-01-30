<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Family;
use App\Models\User;
use App\Models\DocumentReminder;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DocumentService
{
    public function __construct(
        private readonly VercelBlobService $vercelBlob
    ) {
    }

    public function store(User $user, Family $family, array $data, UploadedFile $file): Document
    {
        $pathname = $this->buildStoragePath($user->tenant_id, $family->id, $file);
        $url = $this->vercelBlob->upload($file, $pathname);

        // The password_hash mutator will automatically hash the password, so pass plain text
        $document = Document::create([
            'tenant_id' => $user->tenant_id,
            'family_id' => $family->id,
            'family_member_id' => $data['family_member_id'] ?? null,
            'uploaded_by' => $user->id,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
            'password_hash' => ($data['is_sensitive'] ?? false) && !empty($data['password']) ? $data['password'] : null,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $url,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $this->syncReminders($document);

        return $document;
    }

    public function updateMetadata(Document $document, array $data): Document
    {
        $updateData = [
            'title' => $data['title'] ?? $document->title,
            'document_type' => $data['document_type'] ?? $document->document_type,
            'is_sensitive' => array_key_exists('is_sensitive', $data) ? (bool) $data['is_sensitive'] : $document->is_sensitive,
            'family_member_id' => $data['family_member_id'] ?? $document->family_member_id,
            'expires_at' => $data['expires_at'] ?? $document->expires_at,
        ];

        // Handle password - only update if new password is provided
        if (isset($data['password']) && !empty($data['password'])) {
            // New password provided - mutator will hash it
            $updateData['password_hash'] = $data['password'];
        } elseif (array_key_exists('is_sensitive', $data) && !$data['is_sensitive']) {
            // Document is no longer sensitive - remove password
            $updateData['password_hash'] = null;
        } elseif (!isset($data['password']) && !$document->is_sensitive && ($data['is_sensitive'] ?? false)) {
            // Document is being made sensitive but no password provided - keep existing or set null
            $updateData['password_hash'] = null;
        }
        // If password is not in data and document stays sensitive, don't touch password_hash

        $document->fill($updateData);
        $document->save();
        $this->syncReminders($document);

        return $document;
    }

    public function delete(Document $document): void
    {
        if (!empty($document->file_path) && str_starts_with((string) $document->file_path, 'https://')) {
            $this->vercelBlob->delete($document->file_path);
        }
        $document->delete();
    }

    private function buildStoragePath(int $tenantId, int $familyId, UploadedFile $file): string
    {
        $ext = $file->getClientOriginalExtension();
        $hashed = Str::uuid()->toString();
        $directory = "documents/tenant-{$tenantId}/family-{$familyId}";

        return "{$directory}/{$hashed}.{$ext}";
    }

    private function syncReminders(Document $document): void
    {
        if (!$this->supportsReminders($document)) {
            $document->reminders()->delete();
            return;
        }

        $expiry = CarbonImmutable::parse($document->expires_at);
        $dates = collect([
            $expiry->subDays(30),
            $expiry->subDays(7),
            $expiry,
        ])->filter(fn (CarbonImmutable $date) => $date->isToday() || $date->isFuture())
            ->map(fn (CarbonImmutable $date) => $date->toDateString());

        foreach ($dates as $date) {
            DocumentReminder::updateOrCreate(
                ['document_id' => $document->id, 'remind_at' => $date],
                []
            );
        }

        $document->reminders()->whereNotIn('remind_at', $dates)->delete();
    }

    private function supportsReminders(Document $document): bool
    {
        if ($document->expires_at === null) {
            return false;
        }

        $builtInSupportedTypes = ['PASSPORT', 'DRIVING_LICENSE', 'INSURANCE'];
        if (in_array($document->document_type, $builtInSupportedTypes, true)) {
            return true;
        }

        $customType = \App\Models\DocumentType::where('tenant_id', $document->tenant_id)
            ->where('slug', $document->document_type)
            ->first();

        return $customType?->supports_expiry ?? false;
    }
}