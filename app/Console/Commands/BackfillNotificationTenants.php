<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillNotificationTenants extends Command
{
    protected $signature = 'notifications:backfill-tenant';

    protected $description = 'Backfill tenant_id for notifications missing it';

    public function handle(): int
    {
        $updated = 0;
        $this->info('Backfilling notifications tenant_id...');

        Notification::whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$updated) {
                foreach ($chunk as $notification) {
                    $user = User::find($notification->user_id);
                    if (!$user || !$user->tenant_id) {
                        continue;
                    }
                    $notification->tenant_id = $user->tenant_id;
                    $notification->save();
                    $updated++;
                }
            });

        $this->info("Backfill complete. Updated {$updated} notifications.");

        return Command::SUCCESS;
    }
}






