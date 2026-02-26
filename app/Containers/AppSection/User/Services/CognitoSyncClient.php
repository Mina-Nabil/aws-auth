<?php

namespace App\Containers\AppSection\User\Services;

use App\Containers\AppSection\User\Exceptions\CognitoInvalidPasswordException;
use App\Containers\AppSection\User\Exceptions\CognitoUsernameExistsException;
use App\Containers\AppSection\User\Exceptions\CognitoUserNotFoundException;
use App\Containers\AppSection\User\Models\CognitoUser;
use Illuminate\Support\Facades\Password;
use App\Ship\Services\CognitoClient;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Exceptions\MethodNotSupportedException;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use InvalidArgumentException;

/**
 * A class for handling Cognito API functions like User Creation, Password Reset, etc.
 */
final class CognitoSyncClient extends CognitoClient
{

    /**
     * Get user details.
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_GetUser.html.
     *
     * @param string $username
     * @return CognitoUser
     * @throws CognitoUserNotFoundException
     */
    public function getUser($username)
    {
        try {
            $user = $this->client->AdminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                throw new CognitoUserNotFoundException();
            }
            throw $e;
        }

        return new CognitoUser($user->get('Username'), $user->get('UserStatus') === 'CONFIRMED');
    }




    /**
     * Register a new User in the AWS Cognito user pool.
     * 
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminCreateUser.html.
     *
     * @param string $userName
     * @param string $password
     * @param array $attributes
     * @return CognitoUser
     * @throws InvalidArgumentException
     * @throws MethodNotSupportedException
     * @throws CognitoUsernameExistsException
     * @throws CognitoIdentityProviderException
     */
    public function createUser(string $userName, string $password, array $attributes = []): CognitoUser
    {
        if (!$password) {
            throw new InvalidArgumentException('Password is required to create a user in the AWS Cognito user pool');
        }

        if (!(config('cognito-auth.sync_user_pool_active'))) {
            throw new MethodNotSupportedException();
        }

        try {
            $response = $this->client->AdminCreateUser([
                'UserPoolId' => $this->poolId,
                'Username' => $userName,
                'UserAttributes' => $this->formatAttributes($attributes),
                // Suppress welcome/invitation message; we set the password ourselves.
                'MessageAction' => 'SUPPRESS',
            ]);
            $this->setUserPassword($userName, $password);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                throw new CognitoUsernameExistsException();
            }

            throw $e;
        }

        return new CognitoUser($response['User']['Username'], false);
    }


    /**
     * Sets the specified user's password in a user pool as an administrator.
     * Backend can use this function to set a user's password.
     * 
     * Password change functions should be called directly from the frontend app, unless inapp auth endpoints are active.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=true => password change functions can be called from the backend app.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=false => password change functions must be called directly from the frontend app.
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminSetUserPassword.html
     *
     * @param string $username
     * @param string $password
     * @param bool $permanent
     * @return bool
     * @throws MethodNotSupportedException
     * @throws CognitoIdentityProviderException
     * @throws CognitoUserNotFoundException
     * @throws CognitoInvalidPasswordException
     */
    public function setUserPassword($username, $password, $permanent = true)
    {
        if (!(config('cognito-auth.sync_user_pool_active'))) {
            throw new MethodNotSupportedException();
        }

        try {
            $this->client->adminSetUserPassword([
                'Password' => $password,
                'Permanent' => $permanent,
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                throw new CognitoUserNotFoundException();
            }

            if ($e->getAwsErrorCode() === self::INVALID_PASSWORD) {
                throw new CognitoInvalidPasswordException();
            }

            throw $e;
        }

        return Password::PASSWORD_RESET;
    }


    /**
     * Backend can use this function to delete a user.
     * COGNITO_SYNC_USER_POOL_ACTIVE=true && COGNITO_SYNC_USER_DELETION=true => deleting a user will keep the userpool in sync.
     * else => deleting a user will not update the userpool.
     * @param string $username
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindeleteuser
     * 
     * @throws MethodNotSupportedException if the user pool is not active or the user deletion is not enabled
     * @throws CognitoIdentityProviderException 
     * @throws CognitoUserNotFoundException if the user is not found in the user pool
     */
    public function deleteUser($username, $overrideSyncDeletionConfig = false)
    {
        if (!(config('cognito-client.sync_user_deletion') || $overrideSyncDeletionConfig) || !config('cognito-auth.sync_user_pool_active')) {
            //if overrideSyncDeletionConfig is true, it will delete the user from the user pool regardless of the sync_user_deletion config
            //as long as the sync_user_pool_active config is true
            throw new MethodNotSupportedException();
        }

        try {
            $this->client->adminDeleteUser([
                'UserPoolId' => $this->poolId,
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                throw new CognitoUserNotFoundException();
            }

            throw $e;
        }
    }

    // HELPER FUNCTIONS

    /**
     * Set a users attributes.
     * 
     * COGNITO_SYNC_USER_POOL_ACTIVE=true => updating a user's attributes will keep the userpool in sync.
     * COGNITO_SYNC_USER_POOL_ACTIVE=false => updating a user's attributes will not update the userpool.
     * 
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminUpdateUserAttributes.html.
     *
     * @param string $username
     * @param array $attributes
     * @return bool
     * @throws MethodNotSupportedException
     * @throws CognitoIdentityProviderException
     */
    public function setUserAttributes($username, array $attributes)
    {
        if (!(config('cognito-auth.sync_user_pool_active'))) {
            throw new MethodNotSupportedException();
        }

        try {
            $this->client->AdminUpdateUserAttributes([
                'Username' => $username,
                'UserPoolId' => $this->poolId,
                'UserAttributes' => $this->formatAttributes($attributes),
            ]);
        } catch (CognitoIdentityProviderException $e) {
            throw $e;
        }

        return true;
    }
}
