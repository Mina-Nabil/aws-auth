<?php

namespace App\Containers\AppSection\CognitoAuth\Services\AWS;

use App\Containers\AppSection\CognitoAuth\Exceptions\InvalidCodeException;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserAlreadyConfirmedException;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserNotFoundException;
use App\Containers\AppSection\CognitoAuth\Exceptions\InvalidPasswordException;
use App\Containers\AppSection\CognitoAuth\Exceptions\UsernameExistsException;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserRegNotConfirmedException;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use App\Ship\Services\CognitoClient;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Lang;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * A class for handling Cognito API functions like User Creation, Password Reset, etc.
 */
class CognitoAuthClient extends CognitoClient
{

    /**
     * Checks if credentials of a user are valid. 
     * Login functions should be called directly from the frontend app, unless inapp auth endpoints are active.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=true => login functions can be called from the backend app.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=false => login functions must be called directly from the frontend app.
     *
     * @see http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminInitiateAuth.html
     * @param string $email
     * @param string $password
     * @return \Aws\Result|bool
     * 
     * @throws UserRegNotConfirmedException When user registration is not confirmed
     * @throws UserNotFoundException When user is not found in Cognito User Pool
     * @throws InvalidPasswordException When password is invalid
     * @throws InvalidRequestException When user is not authorized to access Cognito User Pool
     */
    public function authenticate($email, $password)
    {
        try {
            $response = $this->client->adminInitiateAuth([
                'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
                'AuthParameters' => [
                    'USERNAME' => $email,
                    'PASSWORD' => $password,
                    'SECRET_HASH' => $this->cognitoSecretHash($email),
                ],
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            $code = $exception->getAwsErrorCode();
            if ($code === self::USER_NOT_CONFIRMED) {
                throw new UserRegNotConfirmedException('User registration not confirmed.', 0, $exception);
            }

            if ($code === self::USER_NOT_FOUND) {
                throw new UserNotFoundException('User not found in Cognito User Pool.', 0, $exception);
            }

            if ($code === self::INVALID_PASSWORD) {
                throw new InvalidPasswordException('Invalid password.', 0, $exception);
            }

            if ($code === self::NOT_AUTHORIZED) {
                throw new InvalidRequestException('User not authorized to access Cognito User Pool.', 0, $exception);
            }

            throw $exception;
        }

        return $response;
    }

    /**
     * Registers a user in the given user pool. 
     * Registration functions should be called directly from the frontend app, unless inapp auth endpoints are active.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=true => registration functions can be called from the backend app.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=false => registration functions must be called directly from the frontend app.
     *
     * @param $email
     * @param $password
     * @param array $attributes
     * @return array [bool True if user is already confirmed, false if confirmation required, string $userSub]
     * @throws UsernameExistsException When email already exists in Cognito
     */
    public function register($email, $password, array $attributes = [])
    {
        $attributes['email'] = $email;

        try {
            $response = $this->client->signUp([
                'ClientId' => $this->clientId,
                'Password' => $password,
                'SecretHash' => $this->cognitoSecretHash($email),
                'UserAttributes' => $this->formatAttributes($attributes),
                'Username' => $email,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                throw new UsernameExistsException('A user with this email already exists.', 0, $e);
            }

            throw $e;
        }

        return [(bool)$response['UserConfirmed'], $response['UserSub']];
    }

    /**
     * Send a password reset code to a user. 
     * Password reset functions should be called directly from the frontend app, unless inapp auth endpoints are active.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=true => password reset functions can be called from the backend app.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=false => password reset functions must be called directly from the frontend app.
     * @see http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ForgotPassword.html
     *
     * @param string $username
     * @return string
     */
    public function sendResetLink($username)
    {
        try {
            $result = $this->client->forgotPassword([
                'ClientId' => $this->clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            }

            throw $e;
        }

        return Password::RESET_LINK_SENT;
    }

    /**
     * Reset a users password based on reset code. 
     * Password reset functions should be called directly from the frontend app, unless inapp auth endpoints are active.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=true => password reset functions can be called from the backend app.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=false => password reset functions must be called directly from the frontend app.
     * 
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ConfirmForgotPassword.html.
     *
     * @param string $code
     * @param string $username
     * @param string $password
     * @return string
     */
    public function resetPassword($code, $username, $password)
    {
        try {
            $this->client->confirmForgotPassword([
                'ClientId' => $this->clientId,
                'ConfirmationCode' => $code,
                'Password' => $password,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            }

            if ($e->getAwsErrorCode() === self::INVALID_PASSWORD) {
                return Lang::has('passwords.password') ? 'passwords.password' : $e->getAwsErrorMessage();
            }

            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                return Password::INVALID_TOKEN;
            }

            throw $e;
        }

        return Password::PASSWORD_RESET;
    }


    /**
     * Set a new password for a user that has been flagged as needing a password change.
     * Password change functions should be called directly from the frontend app, unless inapp auth endpoints are active.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=true => password change functions can be called from the backend app.
     * COGNITO_INCLUDE_AUTH_ENDPOINTS=false => password change functions must be called directly from the frontend app.
     * 
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminRespondToAuthChallenge.html.
     *
     * @param string $username
     * @param string $password
     * @param string $session
     * @return bool
     */
    public function confirmPassword($username, $password, $session)
    {
        try {
            $this->client->AdminRespondToAuthChallenge([
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
                'Session' => $session,
                'ChallengeResponses' => [
                    'NEW_PASSWORD' => $password,
                    'USERNAME' => $username,
                    'SECRET_HASH' => $this->cognitoSecretHash($username),
                ],
                'ChallengeName' => 'NEW_PASSWORD_REQUIRED',
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                return Password::INVALID_TOKEN;
            }

            throw $e;
        }

        return Password::PASSWORD_RESET;
    }


    /**
     * Backend can use this function to invalidate a user's password.
     * 
     * @param string $username
     * @return void
     */
    public function invalidatePassword($username)
    {
        $this->client->adminResetUserPassword([
            'UserPoolId' => $this->poolId,
            'Username' => $username,
        ]);
    }

    /**
     * Backend can use this function to confirm a user's sign up (admin confirm, no code).
     *
     * @param string $username
     * @return void
     * @throws UserNotFoundException When user does not exist in Cognito
     * @throws UserAlreadyConfirmedException When user is already confirmed
     */
    public function confirmSignUp($username)
    {
        try {
            $this->client->adminConfirmSignUp([
                'UserPoolId' => $this->poolId,
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                throw new UserNotFoundException('User not found.', 0, $e);
            }
            if ($e->getAwsErrorCode() === 'NotAuthorizedException' && str_contains($e->getAwsErrorMessage() ?? '', 'CONFIRMED')) {
                throw new UserAlreadyConfirmedException('User is already confirmed.', 0, $e);
            }
            throw $e;
        }
    }

    /**
     * Backend can use this function to confirm a user's sign up.
     * An user can use this function to confirm their sign up by entering the confirmation code sent to their email.
     * 
     * @param string $username
     * @param string $confirmationCode
     * @return void
     */
    public function confirmUserSignUp($username, $confirmationCode)
    {
        try {
            $this->client->confirmSignUp([
                'ClientId' => $this->clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
                'ConfirmationCode' => $confirmationCode,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                throw new UserNotFoundException('User not found.', 0, $e);
            }

            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                throw new InvalidCodeException('Invalid code, expired or expired', 0, $e);
            }

            if ($e->getAwsErrorCode() === 'NotAuthorizedException' and $e->getAwsErrorMessage() === 'User cannot be confirmed. Current status is CONFIRMED') {
                throw new UserAlreadyConfirmedException('User is already confirmed.', 0, $e);
            }

            throw $e;
        }
    }

    /**
     * Backend can use this function to resend a confirmation code to a user.
     * Should be handled directly by AWS Cognito or by frontend application
     * 
     * @param string $username
     * @return void
     */
    public function resendToken($username)
    {
        try {
            $this->client->resendConfirmationCode([
                'ClientId' => $this->clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $e) {

            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return 'validation.invalid_user';
            }

            if ($e->getAwsErrorCode() === 'LimitExceededException') {
                return 'validation.exceeded';
            }

            if ($e->getAwsErrorCode() === 'InvalidParameterException') {
                return 'validation.confirmed';
            }

            throw $e;
        }
    }


    /**
     * Sign out the user from all devices (invalidates all tokens for this user).
     * Call this with the current access token when the user logs out.
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_GlobalSignOut.html
     * @param string $accessToken The access token (e.g. Bearer token from the request)
     * @return bool True if sign-out succeeded, false if token invalid/expired (caller may still respond success)
     */
    public function globalSignOut(string $accessToken): bool
    {
        try {
            $this->client->globalSignOut([
                'AccessToken' => $accessToken,
            ]);
            return true;
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::NOT_AUTHORIZED) {
                return false;
            }
            throw $e;
        }
    }


    /**
     * Refresh the user's access token using the current refresh token.
     *
     * @param string $refreshToken The refresh token to refresh
     * @throws CognitoIdentityProviderException When the refresh token is invalid or expired
     * @return \Aws\Result The new access token
     */
    public function refreshToken(string $refreshToken): \Aws\Result
    {
        try {
            return $this->client->getTokensFromRefreshToken([
                'ClientId' => $this->clientId,
                'ClientSecret' => $this->clientSecret,
                'RefreshToken' => $refreshToken,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            throw $e;
        }
    }

    /**
     * Revoke a refresh token so it can no longer be used to obtain new access/id tokens.
     * Use when the client sends the refresh token to invalidate it server-side.
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_RevokeToken.html
     * @param string $refreshToken The refresh token to revoke
     * @return void
     */
    public function revokeRefreshToken(string $refreshToken): void
    {
        $this->client->revokeToken([
            'ClientId' => $this->clientId,
            'ClientSecret' => $this->clientSecret,
            'Token' => $refreshToken,
        ]);
    }
}
