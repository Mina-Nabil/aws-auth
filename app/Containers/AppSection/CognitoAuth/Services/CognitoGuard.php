<?php

namespace App\Containers\AppSection\CognitoAuth\Services;

use App\Containers\AppSection\CognitoAuth\Exceptions\InvalidTokenException;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoTokenService;
use App\Ship\Parents\Exceptions\MethodNotSupportedException;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

/**
 * A class for handling Cognito authentication.
 */
class CognitoGuard implements Guard
{
    use GuardHelpers;

    /**
     * @param EloquentUserProvider $provider - The user provider.
     * @param Request  $request - The request instance.
     *
     * @return void
     */
    public function __construct(EloquentUserProvider $provider, protected Request $request)
    {
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
     *
     * @throws
     * @return Authenticatable|null
     */
    public function user()
    {
        if ($this->user  instanceof Authenticatable) {
            return $this->user;
        }

        $ts = app()->make(CognitoTokenService::class);
        $jwt = $ts->getTokenFromRequest($this->request);

        if (! $jwt) {
            return null;
        }

        // Get the model class based on the user provider configuration
        $userProvider = config('cognito-auth.user_provider') === 'cognito_users' ? 'cognito_users' : 'users';
        $cognitoIdColumn = $userProvider === 'cognito_users' ? 'id' : 'cognito_id';
        $createUserIfNotExists = $userProvider === 'cognito_users';

        $modelClass = config('auth.providers.' . $userProvider . '.model');

        try {
            $cognitoUuid = $ts->getCognitoUuidFromToken($jwt);
            if ($cognitoUuid) {
                $this->user = $modelClass::where($cognitoIdColumn, $cognitoUuid)->first();
                if (!$this->user && $createUserIfNotExists) {
                    $this->user = $modelClass::create([
                        'cognito_id' => $cognitoUuid,
                    ]);
                }
                return $this->user;
            }
        } catch (InvalidTokenException $e) {
            // Unable to get cognito uuid from token, so try to get user by email
        }

        $email = $ts->getEmailFromToken($jwt);
        if ($email) {
            $this->user = $modelClass::where('email', $email)->first();
            return $this->user;
        }


        return null;
    }

    /**
     * @param  array  $credentials
     * @throws MethodNotSupportedException
     */
    public function validate(array $credentials = [])
    {
        throw new MethodNotSupportedException('CognitoGuard does not support the validate method.');
    }

    /**
     * @param  array  $credentials
     * @throws MethodNotSupportedException
     */
    public function attempt(array $credentials = [])
    {
        throw new MethodNotSupportedException('CognitoGuard does not support the attempt method.');
    }
}
