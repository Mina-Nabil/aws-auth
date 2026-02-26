<?php

use App\Containers\AppSection\Notification\API\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'notifications'], function () {

    Route::get('/', [NotificationController::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::post('/', [NotificationController::class, 'update'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
