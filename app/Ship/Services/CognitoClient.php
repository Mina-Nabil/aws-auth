<?php

namespace App\Ship\Services;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

abstract class CognitoClient
{
    const NEW_PASSWORD_CHALLENGE = 'NEW_PASSWORD_REQUIRED';
    const FORCE_PASSWORD_STATUS = 'FORCE_CHANGE_PASSWORD';
    const RESET_REQUIRED = 'PasswordResetRequiredException';
    const USER_NOT_FOUND = 'UserNotFoundException';
    const NOT_AUTHORIZED = 'NotAuthorizedException';
    const USERNAME_EXISTS = 'UsernameExistsException';
    const INVALID_PASSWORD = 'InvalidPasswordException';
    const CODE_MISMATCH = 'CodeMismatchException';
    const EXPIRED_CODE = 'ExpiredCodeException';
    const USER_NOT_CONFIRMED = 'UserNotConfirmedException';
    const INVALID_GRANT = 'InvalidGrantException';
    const UNSUPPORTED_FEATURE = 'Unsupported';

    /**
     * @var CognitoIdentityProviderClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $poolId;

    /**
     * CognitoClient constructor.
     * @param CognitoIdentityProviderClient $client
     * @param string $clientId
     * @param string $clientSecret
     * @param string $poolId
     */
    public function __construct(
        CognitoIdentityProviderClient $client,
        $clientId,
        $clientSecret,
        $poolId
    ) {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->poolId = $poolId;
    }

    /**
     * Format attributes in Name/Value array. 
     *
     * @param array $attributes
     * @return array
     */
    final protected function formatAttributes(array $attributes)
    {
        $userAttributes = [];

        foreach ($attributes as $key => $value) {
            $userAttributes[] = [
                'Name' => $key,
                'Value' => $value,
            ];
        }

        return $userAttributes;
    }

    /**
     * Creates a HMAC from a string.
     *
     * @param string $message
     * @return string
     */
    final protected function hash($message)
    {
        $hash = hash_hmac(
            'sha256',
            $message,
            $this->clientSecret,
            true
        );

        return base64_encode($hash);
    }

    /**
     * Creates the Cognito secret hash.
     * 
     * @param string $username
     * @return string
     */
    final protected function cognitoSecretHash($username)
    {
        return $this->hash($username . $this->clientId);
    }
}
