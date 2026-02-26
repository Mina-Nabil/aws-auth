<?php

namespace App\Containers\AppSection\Authentication\Providers;

use App\Ship\Parents\Providers\ServiceProvider as ParentServiceProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Guards\TokenGuard;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Policies\UserPolicy;

final class AuthServiceProvider extends ParentServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {

        $method = function () {
            foreach (array_keys(config('auth.guards')) as $guard) {
                if (Auth::guard($guard)->check()) {
                    return $guard;
                }
            }

            return null;
        };
        /*
         * Get the current logged-in user guard.
         *
         * @return string|null
         */
        SessionGuard::macro('activeGuard', $method);
        TokenGuard::macro('activeGuard', $method);
    }
}
