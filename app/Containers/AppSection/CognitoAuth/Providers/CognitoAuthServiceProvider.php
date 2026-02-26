<?php

namespace App\Containers\AppSection\CognitoAuth\Providers;

use App\Containers\AppSection\Authorization\Data\Seeders\SuperAdminSeeder_2;
use App\Containers\AppSection\CognitoAuth\Data\Seeders\SuperAdminSeeder_2 as CognitoAuthSuperAdminSeeder_2;
use App\Containers\AppSection\CognitoAuth\Enums\CognitoAuthGate;
use App\Containers\AppSection\CognitoAuth\Policies\CognitoAuthPolicy;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoAuthClient;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoJWKsService;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoTokenService;
use App\Containers\AppSection\CognitoAuth\Services\LocalCognito\LocalCognitoJWKsService;
use App\Containers\AppSection\CognitoAuth\Services\LocalCognito\LocalCognitoTokenService;
use App\Containers\AppSection\CognitoAuth\Services\CognitoGuard;
use App\Containers\AppSection\User\Services\CognitoSyncClient;
use App\Ship\Parents\Providers\ServiceProvider as ParentServiceProvider;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;

final class CognitoAuthServiceProvider extends ParentServiceProvider
{
    public function boot(): void
    {
        Gate::define(CognitoAuthGate::CONFIRM_SIGN_UP, [CognitoAuthPolicy::class, 'confirmSignUp']);

        if (!config('cognito-auth.auth_active')) return;

        $this->registerCognitoAuthClient();

        // Singleton for the CognitoJWKsService, fetching the JWKS from the Cognito user pool.
        $this->app->singleton(CognitoJWKsService::class, function (Application $app) {
            if (config('cognito-auth.endpoint')) {
                //endpoint is set, so we are using local Cognito user pool
                return new LocalCognitoJWKsService();
            } else {
                //endpoint is not set, so we are using AWS Cognito user pool
                return new CognitoJWKsService();
            }
        });

        // Singleton for the CognitoTokenService, decoding the token and getting the User UUID from the Cognito Auth token.
        $this->app->singleton(CognitoTokenService::class, function (Application $app) {
            if (config('cognito-auth.endpoint')) {
                //endpoint is set, so we are using local Cognito user pool
                return new LocalCognitoTokenService();
            } else {
                //endpoint is not set, so we are using AWS Cognito user pool
                return new CognitoTokenService();
            }
        });

        // Singleton for the CognitoGuard, handling the authentication functions.
        $this->app->singleton(CognitoGuard::class, function (Application $app) {
            return new CognitoGuard(
                $app['auth']->createUserProvider('users'),
                $app['request']
            );
        });

        // Extend the auth guard to use the CognitoGuard class in Laravel Auth. 
        // The new 'cognito' guard will be used in the auth.guards.api.driver config.
        $this->app['auth']->extend('cognito', function ($app, $name, array $config) {
            $guard = $app->make(CognitoGuard::class);
            return $guard;
        });

        // Register the SuperAdminSeeder_2 seeder to create the super admin user in the AWS Cognito user pool.
        if (config('cognito-auth.user_provider') === 'cognito_users') {
            $this->app->singleton(SuperAdminSeeder_2::class, function (Application $app) {
                return new CognitoAuthSuperAdminSeeder_2(
                    $app->make(CognitoSyncClient::class),
                );
            });
        }
    }

    private function registerCognitoAuthClient(): void
    {
        $this->app->singleton(CognitoAuthClient::class, function (Application $app) {
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
                $config['credentials'] = [
                    'key' => 'local',
                    'secret' => 'local',
                ];
            }

            return new CognitoAuthClient(
                new CognitoIdentityProviderClient($config),
                config('cognito-auth.app_client_id'),
                config('cognito-auth.app_client_secret'),
                config('cognito-auth.user_pool_id')
            );
        });
    }
}
