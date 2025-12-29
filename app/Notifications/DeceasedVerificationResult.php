<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FamilyMember;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DeceasedVerificationResult extends Notification
{
    public function __construct(
        private FamilyMember $member,
        private string $result // 'approved' | 'denied'
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        $statusText = $this->result === 'approved'
            ? 'All votes approved. Member marked as deceased.'
            : 'A vote was denied. Deceased request failed.';

        return new DatabaseMessage([
            'type' => 'deceased_verification_result',
            'title' => 'Deceased verification ' . ($this->result === 'approved' ? 'approved' : 'denied'),
            'message' => "{$this->member->first_name} {$this->member->last_name}: {$statusText}",
            'family_id' => $this->member->family_id,
            'family_member_id' => $this->member->id,
            'result' => $this->result,
        ]);
    }
}








