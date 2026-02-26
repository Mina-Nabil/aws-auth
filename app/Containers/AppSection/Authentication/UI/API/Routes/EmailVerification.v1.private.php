<?php

use App\Containers\AppSection\Authentication\UI\API\Controllers\EmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('email')->group(function () {
    Route::post('/verification-notification', [EmailVerificationController::class, 'sendVerificationNotification'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']))
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    Route::post('/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyEmail'])
            ->middleware(['auth:api', 'signed'])
            ->name('verification.verify');
});
