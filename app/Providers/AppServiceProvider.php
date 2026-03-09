<?php

namespace App\Providers;

use App\Events\ReferralCreated;
use App\Listeners\TriageReferralListener;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ReferralCreated::class, TriageReferralListener::class);

        RateLimiter::for('referral-create', function ($request) {
            return Limit::perMinute(10)
                ->by($request->bearerToken() ?? $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => __('referral.messages.rate_limited'),
                    ], 429);
                });
        });
    }
}
