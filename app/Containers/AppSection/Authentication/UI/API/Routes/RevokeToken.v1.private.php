<?php

use App\Containers\AppSection\Authentication\UI\API\Controllers\RevokeTokenController;
use Illuminate\Support\Facades\Route;

Route::post('logout', RevokeTokenController::class)
    ->middleware(['auth:api']);
