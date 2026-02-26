<?php

use App\Containers\AppSection\File\API\Controllers\FileLinkController;
use Illuminate\Support\Facades\Route;

Route::group([], function () {

    Route::get('/{entity_type}/{entity_id}/files', [FileLinkController::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::post('/{entity_type}/{entity_id}/files', [FileLinkController::class, 'create'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));

    Route::delete('/{entity_type}/{entity_id}/files/{file_id}', [FileLinkController::class, 'destroy'])
        ->when(config('appSection-authentication.authentication_enabled'), fn ($r) => $r->middleware(['auth:api']));
});
