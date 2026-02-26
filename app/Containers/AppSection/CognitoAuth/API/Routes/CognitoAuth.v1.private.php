<?php

use Illuminate\Support\Facades\Route;
use App\Containers\AppSection\CognitoAuth\API\Controllers\CognitoLoginController;
use App\Containers\AppSection\CognitoAuth\API\Controllers\CognitoRegisterController;

if (config('cognito-auth.include_auth_endpoints')) {
    Route::post('cognito/login', [CognitoLoginController::class, 'index']);

    Route::post('cognito/refresh-token', [CognitoLoginController::class, 'refreshToken'])
        ->middleware(['auth:api']);

    Route::post('cognito/logout', [CognitoLoginController::class, 'destroy'])
        ->middleware(['auth:api']);

    Route::get('cognito/register', [CognitoRegisterController::class, 'index'])
        ->middleware(['auth:api']);

    Route::post('cognito/register', [CognitoRegisterController::class, 'store'])
        ->name('cognito.register');

    Route::post('cognito/register/confirm-user', [CognitoRegisterController::class, 'confirmUserSignUp']);

    Route::delete('cognito/register', [CognitoRegisterController::class, 'destroy'])
        ->middleware(['auth:api']);

    Route::put('cognito/register/confirm', [CognitoRegisterController::class, 'update'])
        ->middleware(['auth:api']);
}
