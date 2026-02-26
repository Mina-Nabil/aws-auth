<?php

use App\Containers\AppSection\Authorization\API\Controllers\UserRolesCRUDController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'users/'], function () {
    Route::get('{user_id}/roles', [UserRolesCRUDController::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::put('{user_id}/roles', [UserRolesCRUDController::class, 'sync'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::patch('{user_id}/roles', [UserRolesCRUDController::class, 'assign'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::delete('{user_id}/roles', [UserRolesCRUDController::class, 'destroy'])
        ->middleware(['auth:api']);
});
