<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\Family;
use App\Models\User;

class CalendarEventPolicy
{
    private function isFamilyMember(User $user, Family $family): bool
    {
        return $family->members()->where('user_id', $user->id)->exists()
            || $family->roles()->where('user_id', $user->id)->exists();
    }

    public function viewAny(User $user, Family $family): bool
    {
        return $this->isFamilyMember($user, $family);
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        return $this->isFamilyMember($user, $event->family);
    }

    public function create(User $user, Family $family): bool
    {
        // All family members can add events
        return $this->isFamilyMember($user, $family);
    }

    public function update(User $user, CalendarEvent $event): bool
    {
        // All family members can edit events
        return $this->isFamilyMember($user, $event->family);
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        // All family members can delete events
        return $this->isFamilyMember($user, $event->family);
    }
}

