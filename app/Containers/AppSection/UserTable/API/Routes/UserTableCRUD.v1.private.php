<?php

use App\Containers\AppSection\UserTable\API\Controllers\UserTableController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'users/me/preferences/table-lists'], function () {

    Route::get('/{table_name}', [UserTableController::class, 'show'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::put('/{table_name}', [UserTableController::class, 'store'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::delete('/{table_name}', [UserTableController::class, 'destroy'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
