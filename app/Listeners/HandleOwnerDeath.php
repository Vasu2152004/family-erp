<?php

namespace App\Listeners;

use App\Events\FamilyMemberDeceased;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleOwnerDeath
{
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
        //
    }
}
