<?php

use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use App\Containers\AppSection\User\Middleware\RequirePasswordReset;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'users'], function () {

    Route::get('/', [UserCRUDController::class, 'users'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::get('/profile', [UserCRUDController::class, 'getLoggedInUserProfile'])
        ->when(
            config('appSection-authentication.authentication_enabled'),
            fn ($r) => $r
            ->middleware(['auth:api'])
            ->withoutMiddleware(RequirePasswordReset::class)
        );

    Route::get('/{user_id}', [UserCRUDController::class, 'findUserById'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::post('/', [UserCRUDController::class, 'createUser'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::patch('/{user_id}', [UserCRUDController::class, 'updateUserInfo'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
    Route::patch('/{user_id}/is_disabled', [UserCRUDController::class, 'updateUserStatus'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
    Route::patch('/{user_id}/password', [UserCRUDController::class, 'updateUserPassword'])
        ->when(
            config('appSection-authentication.authentication_enabled'),
            fn ($r) => $r
            ->middleware(['auth:api'])
            ->withoutMiddleware(RequirePasswordReset::class)
        );

    Route::delete('/{user_id}', [UserCRUDController::class, 'deleteUser'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::post('/{user_id}/resend-welcome-email', [UserCRUDController::class, 'resendWelcomeEmail'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
