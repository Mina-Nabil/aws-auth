<?php

use App\Containers\AppSection\Authorization\API\Controllers\RolePermissionsCRUDController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'roles/'], function () {
    Route::get('{role_id}/permissions', [RolePermissionsCRUDController::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::post('{role_id}/permissions', [RolePermissionsCRUDController::class, 'assign'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::put('{role_id}/permissions', [RolePermissionsCRUDController::class, 'sync'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::delete('{role_id}/permissions', [RolePermissionsCRUDController::class, 'destroy'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
