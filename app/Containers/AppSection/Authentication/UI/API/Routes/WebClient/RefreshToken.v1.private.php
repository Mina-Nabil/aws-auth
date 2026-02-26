<?php

use App\Containers\AppSection\Authentication\UI\API\Controllers\WebClient\RefreshTokenController;
use Illuminate\Support\Facades\Route;

Route::post('clients/refresh', RefreshTokenController::class);
