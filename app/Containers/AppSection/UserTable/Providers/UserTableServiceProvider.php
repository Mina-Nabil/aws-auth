<?php

namespace App\Containers\AppSection\UserTable\Providers;

use App\Containers\AppSection\UserTable\Enums\UserTableGate;
use App\Containers\AppSection\UserTable\Policies\UserTablePolicy;
use App\Ship\Parents\Providers\ServiceProvider as ParentServiceProvider;
use Illuminate\Support\Facades\Gate;

final class UserTableServiceProvider extends ParentServiceProvider
{
    public function boot(): void
    {
        // Register Gates
        Gate::define(UserTableGate::LIST_USER_TABLES, [UserTablePolicy::class, 'list']);
        Gate::define(UserTableGate::CREATE_OR_UPDATE_USER_TABLE, [UserTablePolicy::class, 'createOrUpdate']);
        Gate::define(UserTableGate::DELETE_USER_TABLE, [UserTablePolicy::class, 'delete']);
    }
}
