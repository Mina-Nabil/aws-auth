<?php

namespace App\Containers\AppSection\Authorization\Providers;

use App\Ship\Parents\Providers\ServiceProvider as ParentServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\Authorization\Enums\AuthorizationGate;
use App\Containers\AppSection\Authorization\Policies\PermissionPolicy;
use App\Containers\AppSection\Authorization\Policies\RolePolicy;
use App\Containers\AppSection\User\Models\User;

final class AuthorizationServiceProvider extends ParentServiceProvider
{
    public function boot(): void
    {
        //default user to null in case the authentication is also disabled
        Gate::before(function (User|null $user = null, string $ability = null) {
            // Check if authorization is disabled via the auth config
            if (!config('appSection-authorization.authorization_enabled', true)) {
                return true; // Automatically authorize all actions
            }
        });

        Gate::define(AuthorizationGate::GIVE_PERMISSIONS, [PermissionPolicy::class, 'give']);
        Gate::define(AuthorizationGate::VIEW_PERMISSIONS, [PermissionPolicy::class, 'view']);
        Gate::define(AuthorizationGate::VIEW_ANY_PERMISSIONS, [PermissionPolicy::class, 'viewAny']);
        Gate::define(AuthorizationGate::REVOKE_PERMISSIONS, [PermissionPolicy::class, 'revoke']);
        Gate::define(AuthorizationGate::SYNC_PERMISSIONS, [PermissionPolicy::class, 'sync']);
        Gate::define(AuthorizationGate::ASSIGN_ROLES, [RolePolicy::class, 'assign']);
        Gate::define(AuthorizationGate::CREATE_ROLES, [RolePolicy::class, 'create']);
        Gate::define(AuthorizationGate::DELETE_ROLES, [RolePolicy::class, 'delete']);
        Gate::define(AuthorizationGate::VIEW_ROLES, [RolePolicy::class, 'view']);
        Gate::define(AuthorizationGate::VIEW_ANY_ROLES, [RolePolicy::class, 'viewAny']);
        Gate::define(AuthorizationGate::REVOKE_ROLES, [RolePolicy::class, 'revoke']);
        Gate::define(AuthorizationGate::SYNC_ROLES, [RolePolicy::class, 'sync']);
    }
}
