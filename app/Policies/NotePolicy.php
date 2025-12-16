<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FamilyMember;
use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Note $note): bool
    {
        if (!$this->belongsToFamily($user, $note)) {
            return false;
        }

        if ($note->visibility === 'shared') {
            return true;
        }

        if ($note->visibility === 'private') {
            return $user->isFamilyAdmin($note->family_id) || $note->created_by === $user->id;
        }

        // Locked: allow family members; controller will gate content via PIN check
        if ($note->visibility === 'locked') {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Note $note): bool
    {
        if (!$this->belongsToFamily($user, $note)) {
            return false;
        }

        return $user->isFamilyAdmin($note->family_id) || $note->created_by === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $this->update($user, $note);
    }

    private function belongsToFamily(User $user, Note $note): bool
    {
        $role = $user->getFamilyRole($note->family_id);
        $isMember = FamilyMember::where('family_id', $note->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }
}




