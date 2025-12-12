<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DocumentReminder;
use App\Notifications\DocumentExpiryReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendDocumentExpiryReminders extends Command
{
    protected $signature = 'documents:send-expiry-reminders';

    protected $description = 'Send reminders for documents approaching expiry (passport, driving license, insurance)';

    public function handle(): int
    {
        $today = now()->toDateString();
        $reminders = DocumentReminder::with(['document.family.roles.user', 'document.familyMember.user'])
            ->whereDate('remind_at', '<=', $today)
            ->whereNull('sent_at')
            ->limit(200)
            ->get();

        $sentCount = 0;

        foreach ($reminders as $reminder) {
            $document = $reminder->document;

            if (!$document || !$document->expires_at) {
                $reminder->delete();
                continue;
            }

            if (!$this->supportsReminders($document->document_type)) {
                $reminder->delete();
                continue;
            }

            $recipients = $this->buildRecipients($reminder);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new DocumentExpiryReminder($document));
                $sentCount += $recipients->count();
            }

            $reminder->update(['sent_at' => now()]);
            $document->update(['last_notified_at' => now()]);
        }

        $this->info("Sent {$sentCount} document expiry reminders.");

        return Command::SUCCESS;
    }

    private function buildRecipients(DocumentReminder $reminder): Collection
    {
        $users = collect();
        $family = $reminder->document?->family;

        if (!$family) {
            return collect();
        }

        $adminRoles = $family->roles()
            ->whereIn('role', ['OWNER', 'ADMIN'])
            ->with('user')
            ->get();

        foreach ($adminRoles as $role) {
            if ($role->user && $role->user->tenant_id === $reminder->document->tenant_id) {
                $users->push($role->user);
            }
        }

        $linkedUser = $reminder->document->familyMember?->user;
        if ($linkedUser && $linkedUser->tenant_id === $reminder->document->tenant_id) {
            $users->push($linkedUser);
        }

        return $users->unique('id');
    }

    private function supportsReminders(string $documentType): bool
    {
        return in_array($documentType, ['PASSPORT', 'DRIVING_LICENSE', 'INSURANCE'], true);
    }
}





