<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Models\FamilyMember;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        // Check if user belongs to the family
        if (!$this->belongsToFamily($user, $task)) {
            return false;
        }

        // OWNER/ADMIN can view all tasks
        if ($user->isFamilyAdmin($task->family_id)) {
            return true;
        }

        // Assigned members can view tasks assigned to them
        if ($this->isAssignedToUser($user, $task)) {
            return true;
        }

        // All family members can view all tasks (not just assigned ones)
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        if (!$this->belongsToFamily($user, $task)) {
            return false;
        }

        // VIEWER cannot update
        $role = $user->getFamilyRole($task->family_id);
        if ($role && $role->role === 'viewer') {
            return false;
        }

        // OWNER/ADMIN can update all
        if ($user->isFamilyAdmin($task->family_id)) {
            return true;
        }

        // MEMBER can update if assigned to them or if they created it
        if ($role && $role->role === 'member') {
            return $this->isAssignedToUser($user, $task) || $task->created_by === $user->id;
        }

        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        if (!$this->belongsToFamily($user, $task)) {
            return false;
        }

        // Only OWNER/ADMIN can delete
        return $user->isFamilyAdmin($task->family_id);
    }

    /**
     * Check if user can update task status.
     * Owners, Admins, and assigned members can update status.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        if (!$this->belongsToFamily($user, $task)) {
            return false;
        }

        // VIEWER cannot update status
        $role = $user->getFamilyRole($task->family_id);
        if ($role && $role->role === 'viewer') {
            return false;
        }

        // OWNER/ADMIN can always update status
        if ($user->isFamilyAdmin($task->family_id)) {
            return true;
        }

        // Assigned members can update status
        if ($this->isAssignedToUser($user, $task)) {
            return true;
        }

        // MEMBER can also update status if they created the task
        if ($role && $role->role === 'member' && $task->created_by === $user->id) {
            return true;
        }

        return false;
    }

    private function belongsToFamily(User $user, Task $task): bool
    {
        $role = $user->getFamilyRole($task->family_id);
        $isMember = FamilyMember::where('family_id', $task->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $role !== null || $isMember;
    }

    private function isAssignedToUser(User $user, Task $task): bool
    {
        if (!$task->family_member_id) {
            return false;
        }

        return FamilyMember::where('id', $task->family_member_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}

