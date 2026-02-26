<?php

namespace App\Containers\AppSection\User\Providers;

use App\Containers\AppSection\User\Enums\UserGate;
use App\Containers\AppSection\User\Policies\UserPolicy;
use App\Ship\Parents\Providers\ServiceProvider as ParentServiceProvider;
use Illuminate\Foundation\Application;
use App\Containers\AppSection\User\Services\CognitoSyncClient;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

final class UserServiceProvider extends ParentServiceProvider
{
    public function boot(): void
    {
        Password::defaults(static fn() => Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());


        if (config('cognito-auth.sync_user_pool_active')) {
            // Authentication is handled by the AWS Cognito
            // All authentication functions are handled by the CognitoJWKsService, CognitoTokenService, and CognitoGuard classes.
            // User management functions are handled by the CognitoClient class.

            // Singleton for the CognitoClient, handling Cognito API functions like User Creation, Password Reset, etc.
            $this->app->singleton(CognitoSyncClient::class, function (Application $app) {
                $config = [];

                if (config('cognito-auth.endpoint')) {
                    $config['endpoint'] = config('cognito-auth.endpoint');
                }

                $config['region'] = config('cognito-auth.region');
                $config['version'] = config('cognito-auth.version');

                $credentials = config('cognito-auth.credentials');
                $hasExplicitCredentials = ! empty($credentials['key']) && ! empty($credentials['secret']);

                if ($hasExplicitCredentials) {
                    $config['credentials'] = Arr::only($credentials, ['key', 'secret', 'token']);
                } elseif (! empty($config['endpoint'])) {
                    // Custom endpoint (e.g. local Cognito): pass dummy credentials so the SDK
                    // does not fall back to the EC2 instance profile (169.254.169.254).
                    $config['credentials'] = [
                        'key' => 'local',
                        'secret' => 'local',
                    ];
                }

                return new CognitoSyncClient(
                    new CognitoIdentityProviderClient($config),
                    config('cognito-auth.app_client_id'),
                    config('cognito-auth.app_client_secret'),
                    config('cognito-auth.user_pool_id')
                );
            });
        }

        Gate::define(UserGate::CREATE_USER, [UserPolicy::class, 'create']);
        Gate::define(UserGate::VIEW_ANY_USER, [UserPolicy::class, 'viewAny']);
        Gate::define(UserGate::VIEW_USER, [UserPolicy::class, 'view']);
        Gate::define(UserGate::UPDATE_USER, [UserPolicy::class, 'update']);
        Gate::define(UserGate::UPDATE_USER_PASSWORD, [UserPolicy::class, 'updatePassword']);
        Gate::define(UserGate::SEND_TEMPORARY_PASSWORD, [UserPolicy::class, 'sendTemporaryPassword']);
        Gate::define(UserGate::UPDATE_USER_STATUS, [UserPolicy::class, 'updateStatus']);
        Gate::define(UserGate::DELETE_USER, [UserPolicy::class, 'delete']);

        Gate::define(UserGate::LIST_USER_FILES, [UserPolicy::class, 'listFiles']);
        Gate::define(UserGate::ATTACH_ANY_USER_FILES, [UserPolicy::class, 'attachAny']);
        Gate::define(UserGate::ATTACH_USER_FILES, [UserPolicy::class, 'attach']);
        Gate::define(UserGate::DETACH_USER_FILES, [UserPolicy::class, 'detach']);
        Gate::define(UserGate::DETACH_ANY_USER_FILES, [UserPolicy::class, 'detachAny']);
    }
}
