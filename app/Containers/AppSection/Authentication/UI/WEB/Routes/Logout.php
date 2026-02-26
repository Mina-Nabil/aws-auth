<?php

use App\Containers\AppSection\Authentication\UI\WEB\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('logout', LogoutController::class);

Route::post('logout', LogoutController::class)
    ->name('logout');
