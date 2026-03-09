<?php

use App\Http\Controllers\Api\V1\ReferralController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('referrals')->group(function () {
        Route::get('/', [ReferralController::class, 'index']);
        Route::post('/', [ReferralController::class, 'store'])->middleware('throttle:referral-create');
        Route::get('/{id}', [ReferralController::class, 'show']);
        Route::post('/{id}/cancel', [ReferralController::class, 'cancel']);
    });
});
