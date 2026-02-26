<?php

namespace App\Containers\AppSection\CognitoAuth\Actions;

use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoAuthClient;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoTokenService;
use App\Ship\Parents\Actions\Action as ParentAction;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Http\Request;

final class CognitoLogoutAction extends ParentAction
{
    public function __construct(
        private CognitoAuthClient $cognitoClient,
        private CognitoTokenService $tokenService
    ) {}

    /**
     * Sign out the user from Cognito (global sign-out using the current access token).
     * If no Bearer token is sent, nothing is done server-side; client should still clear local tokens.
     * If Cognito rejects the token or does not support global sign-out (e.g. some emulators), we still succeed so the client can clear tokens.
     */
    public function run(Request $request): void
    {
        $accessToken = $this->tokenService->getTokenFromRequest($request);

        if ($accessToken === null || $accessToken === '') {
            return;
        }

        try {
            $this->cognitoClient->globalSignOut($accessToken);
        } catch (CognitoIdentityProviderException) {
            // Token invalid/expired or emulator doesn't support GlobalSignOut; still treat as logged out
        }
    }
}
