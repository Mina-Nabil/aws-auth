<?php

use App\Containers\AppSection\Authentication\UI\API\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword'])
    ->middleware('guest')
    ->name('password.email');

Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])
    ->middleware('guest')
    ->name('password.update');
