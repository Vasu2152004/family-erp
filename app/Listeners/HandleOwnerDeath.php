<?php

namespace App\Listeners;

use App\Events\FamilyMemberDeceased;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class HandleOwnerDeath implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(FamilyMemberDeceased $event): void
    {
        $member = $event->member;

        // Unlock hidden investments and locked assets for this deceased member
        DB::transaction(function () use ($member) {
            // Unlock all hidden investments owned by this member
            \App\Models\Investment::where('family_id', $member->family_id)
                ->where('family_member_id', $member->id)
                ->where('is_hidden', true)
                ->update([
                    'is_hidden' => false,
                    'pin_hash' => null, // Remove PIN protection
                    'updated_at' => now(),
                ]);

            // Unlock all locked assets owned by this member
            \App\Models\Asset::where('family_id', $member->family_id)
                ->where('family_member_id', $member->id)
                ->where('is_locked', true)
                ->update([
                    'is_locked' => false,
                    'pin_hash' => null, // Remove PIN protection
                    'updated_at' => now(),
                ]);
        });
    }
}
