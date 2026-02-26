<?php

namespace App\Containers\AppSection\File\Providers;

use App\Containers\AppSection\File\Enums\FileGate;
use App\Containers\AppSection\File\Jobs\DeleteTemporaryFilesJob;
use App\Containers\AppSection\File\Policies\FilePolicy;
use App\Ship\Parents\Providers\ServiceProvider as ParentServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schedule;

final class FileServiceProvider extends ParentServiceProvider
{
    public function boot(): void
    {
        Gate::define(FileGate::CREATE_FILE, [FilePolicy::class , 'create']);
        Gate::define(FileGate::VIEW_ANY_FILE, [FilePolicy::class , 'viewAny']);
        Gate::define(FileGate::VIEW_FILE, [FilePolicy::class , 'view']);
        Gate::define(FileGate::DOWNLOAD_ANY_FILE, [FilePolicy::class , 'downloadAny']);
        Gate::define(FileGate::UPDATE_FILE, [FilePolicy::class , 'update']);
        Gate::define(FileGate::DELETE_FILE, [FilePolicy::class , 'delete']);

        Schedule::dailyAt('00:00')->job(new DeleteTemporaryFilesJob());
    }
}
