<?php

namespace App\Listeners;

use App\Enums\ReferralStatus;
use App\Events\ReferralCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriageReferralListener implements ShouldQueue
{
    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function handle(ReferralCreated $event): void
    {
        $referral = $event->referral->fresh();

        if ($referral->status !== ReferralStatus::Received) {
            Log::info('Referral skipped triage — not in received status.', [
                'referral_id' => $referral->id,
                'current_status' => $referral->status->value,
            ]);

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

        Log::info('Referral triage completed.', [
            'referral_id' => $referral->id,
            'result' => $accepted ? 'accepted' : 'rejected',
        ]);
    }

    public function failed(ReferralCreated $event, Throwable $exception): void
    {
        $referral = $event->referral->fresh();

        if ($referral->status === ReferralStatus::Triaging) {
            $referral->update(['status' => ReferralStatus::Received]);
        }

        Log::error('Referral triage failed after all retries.', [
            'referral_id' => $referral->id,
            'error' => $exception->getMessage(),
        ]);

        activity()
            ->performedOn($referral)
            ->useLog('referral')
            ->event('triage_failed')
            ->withProperties(['error' => $exception->getMessage()])
            ->log(__('referral.audit.triage_failed'));
    }
}
