<?php

use App\Containers\AppSection\Authorization\API\Controllers\RoleCRUDController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'roles'], function () {
    Route::get('/', [RoleCRUDController::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::get('/{role_id}', [RoleCRUDController::class, 'show'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::post('/', [RoleCRUDController::class, 'create'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));


    Route::delete('/{role_id}', [RoleCRUDController::class, 'destroy'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
