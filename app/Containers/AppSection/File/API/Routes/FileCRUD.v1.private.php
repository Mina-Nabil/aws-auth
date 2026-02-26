<?php

use App\Containers\AppSection\File\API\Controllers\FileCRUDController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'files'], function () {

    Route::get('/', [FileCRUDController::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
    Route::get('/{file_id}/download', [FileCRUDController::class, 'download'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
    Route::get('/{file_id}', [FileCRUDController::class, 'show'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::post('/', [FileCRUDController::class, 'create'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::patch('/{file_id}', [FileCRUDController::class, 'update'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::delete('/{file_id}', [FileCRUDController::class, 'destroy'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
