<?php

use App\Containers\AppSection\Authorization\API\Controllers\PermissionsCRUDController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'permissions'], function () {
    Route::get('/', [PermissionsCRUDController::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::get('/{permission_id}', [PermissionsCRUDController::class, 'show'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
