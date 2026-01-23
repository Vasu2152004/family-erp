<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DeceasedVerificationStarted extends Notification
{
    public function __construct(
        private FamilyMember $member,
        private User $requester
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'deceased_verification_started',
            'title' => 'Deceased verification started',
            'message' => "{$this->requester->name} started a deceased verification for {$this->member->first_name} {$this->member->last_name}. Please vote.",
            'family_id' => $this->member->family_id,
            'family_member_id' => $this->member->id,
            'requested_by' => $this->requester->id,
        ]);
    }
}

















