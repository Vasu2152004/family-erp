<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class NoteService
{
    public function create(array $data, int $tenantId, int $familyId, int $userId): Note
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $userId) {
            $note = Note::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'visibility' => $data['visibility'],
                'pin_hash' => $this->hashPinIfNeeded($data),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return $note;
        });
    }

    public function update(Note $note, array $data, int $userId): Note
    {
        return DB::transaction(function () use ($note, $data, $userId) {
            $update = [
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'visibility' => $data['visibility'],
                'updated_by' => $userId,
            ];

            if ($note->visibility !== 'locked' && $data['visibility'] === 'locked') {
                // Must have pin provided
                $update['pin_hash'] = $this->hashPinIfNeeded($data);
            } elseif ($data['visibility'] === 'locked' && !empty($data['pin'])) {
                $update['pin_hash'] = $this->hashPinIfNeeded($data);
            } elseif ($data['visibility'] !== 'locked') {
                // Clear pin if unlocking
                $update['pin_hash'] = null;
            }

            $note->update($update);

            // Reset unlocked session if visibility changed
            Session::forget($this->sessionKey($note));

            return $note->fresh();
        });
    }

    public function delete(Note $note): void
    {
        DB::transaction(function () use ($note) {
            $note->delete();
            Session::forget($this->sessionKey($note));
        });
    }

    public function unlock(Note $note, string $pin): bool
    {
        if (!$note->pin_hash) {
            return false;
        }

        if (Hash::check($pin, $note->pin_hash)) {
            Session::put($this->sessionKey($note), true);
            return true;
        }

        return false;
    }

    public function isUnlocked(Note $note): bool
    {
        return (bool) Session::get($this->sessionKey($note), false);
    }

    private function hashPinIfNeeded(array $data): ?string
    {
        if (($data['visibility'] ?? null) !== 'locked') {
            return null;
        }

        return isset($data['pin']) ? Hash::make($data['pin']) : null;
    }

    private function sessionKey(Note $note): string
    {
        return "note_unlocked_{$note->id}";
    }
}
