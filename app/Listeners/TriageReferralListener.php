<?php

namespace App\Listeners;

use App\Enums\ReferralStatus;
use App\Events\ReferralCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TriageReferralListener implements ShouldQueue
{
    public function handle(ReferralCreated $event): void
    {
        $referral = $event->referral;

        if ($referral->status !== ReferralStatus::Received) {
            return;
        }

        $referral->update(['status' => ReferralStatus::Triaging]);

        sleep(5); // some magic happens during this time :-)

        $accepted = rand(1, 100) <= 80; // randomly 80% acceptance rate :D

        $referral->update([
            'status' => $accepted 
                ? ReferralStatus::Accepted 
                : ReferralStatus::Rejected,
            'triaged_at' => now(),
        ]);
    }
}
