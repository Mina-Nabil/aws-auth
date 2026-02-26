<?php

namespace App\Containers\AppSection\CognitoAuth\Actions;

use App\Containers\AppSection\CognitoAuth\Models\LoginResponse;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoAuthClient;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use App\Ship\Parents\Exceptions\ThirdPartyException;
use App\Ship\Services\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Exception;

final class CognitoRefreshTokenAction extends ParentAction
{
    public function __construct(
        private CognitoAuthClient $cognitoClient
    ) {}

    /**
     * Refresh the user's access token using the current refresh token.
     */
    public function run($refreshToken): LoginResponse
    {
        try {
            $response = $this->cognitoClient->refreshToken($refreshToken);
            return new LoginResponse($response->get('AccessToken'), $response->get('IdToken'), $response->get('RefreshToken'), $response->get('ExpiresIn'));
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === CognitoClient::UNSUPPORTED_FEATURE) {
                throw new ThirdPartyException('Unsupported feature.', 0, $e);
            }
            throw new InvalidRequestException('Invalid refresh token.', 0, $e);
        } catch (Exception $e) {
            throw new ThirdPartyException('Error refreshing token.', 0, $e);
        }
    }
}
