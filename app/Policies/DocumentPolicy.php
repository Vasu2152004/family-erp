<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\FamilyMember;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if (!$this->belongsToFamily($user, $document)) {
            return false;
        }

        if ($document->is_sensitive) {
            return $user->isFamilyAdmin($document->family_id) || $document->isLinkedMember($user);
        }

        return true;
    }

    public function download(User $user, Document $document): bool
    {
        // For downloads, allow any family member to attempt download
        // The controller will check password requirements and session access
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        return $this->belongsToFamily($user, $document);
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        return $user->isFamilyAdmin($document->family_id);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function create(User $user, int $familyId): bool
    {
        return $user->isFamilyAdmin($familyId);
    }

    private function belongsToFamily(User $user, Document $document): bool
    {
        $role = $user->getFamilyRole($document->family_id);
        $isMember = FamilyMember::where('family_id', $document->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }
}

