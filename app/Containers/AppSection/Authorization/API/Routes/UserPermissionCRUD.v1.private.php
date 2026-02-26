<?php

use App\Containers\AppSection\Authorization\API\Controllers\UserPermissionsCRUDController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'users/'], function () {
    Route::get('{user_id}/permissions', [UserPermissionsCRUDController::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::post('{user_id}/permissions', [UserPermissionsCRUDController::class, 'assign'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::delete('{user_id}/permissions', [UserPermissionsCRUDController::class, 'destroy'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
