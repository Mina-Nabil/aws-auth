<?php

namespace App\Containers\AppSection\CognitoAuth\Actions;

use App\Containers\AppSection\CognitoAuth\API\Requests\LoginRequest;
use App\Containers\AppSection\CognitoAuth\Models\LoginResponse;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoAuthClient;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoTokenService;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserRegNotConfirmedException;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserNotFoundException;
use App\Containers\AppSection\CognitoAuth\Exceptions\InvalidPasswordException;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use App\Ship\Parents\Exceptions\ThirdPartyException;
use Exception;

final class CognitoLoginAction extends ParentAction
{

    public function __construct(private CognitoAuthClient $cognitoClient, private CognitoTokenService $cognitoTokenService) {}

    /**
     * Login using email and password saved in the AWS Cognito user pool
     * 
     * @param LoginRequest $request
     * @return LoginResponse
     * @throws InvalidRequestException
     * @throws Exception
     */
    public function run(LoginRequest $request): LoginResponse
    {
        try {

            $response = $this->cognitoClient->authenticate($request->email, $request->password);
        } catch (UserRegNotConfirmedException $e) {
            throw new InvalidRequestException('User not confirmed. Please check with your application admin to confirm your account.', 0, $e);
        } catch (UserNotFoundException $e) {
            throw new InvalidRequestException('User not found in Cognito User Pool.', 0, $e);
        } catch (InvalidPasswordException $e) {
            throw new InvalidRequestException('Invalid password for AWS Cognito user.', 0, $e);
        } catch (InvalidRequestException $e) {
            throw new InvalidRequestException('User not authorized to access Cognito User Pool.', 0, $e);
        } catch (Exception $e) {
            throw new ThirdPartyException('Error logging in.', 0, $e);
        }

        if ($response->get('AuthenticationResult')) {
            $authenticationResult = $response->get('AuthenticationResult');
            if(!in_array('ExpiresIn', array_keys($authenticationResult))) {
                $authenticationResult['ExpiresIn'] = $this->getExpirationTimeFromToken($authenticationResult['AccessToken']);
            }
            return new LoginResponse($authenticationResult['AccessToken'], $authenticationResult['IdToken'], $authenticationResult['RefreshToken'], $authenticationResult['ExpiresIn']);
        }

        throw new InvalidRequestException('Invalid credentials');
    }

    private function getExpirationTimeFromToken(string $jwtAccessToken): int
    {
        return $this->cognitoTokenService->getExpirationTimeFromToken($jwtAccessToken);
    }
}
