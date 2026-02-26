<?php

namespace App\Containers\AppSection\CognitoAuth\Actions;

use App\Containers\AppSection\CognitoAuth\API\Requests\ConfirmUserSignUpRequest;
use App\Containers\AppSection\CognitoAuth\Exceptions\InvalidCodeException;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserAlreadyConfirmedException;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserNotFoundException;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoAuthClient;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use App\Ship\Parents\Exceptions\ThirdPartyException;
use Exception;

final class CognitoConfirmUserSignUpAction extends ParentAction
{
    public function __construct(
        private CognitoAuthClient $cognitoClient,
    ) {}

    /**
     * User confirms their sign-up with the confirmation code sent to their email.
     * This endpoint is public (no auth required) as the user has not logged in yet.
     *
     * @throws InvalidRequestException When validation fails (invalid user, code, already confirmed, etc.)
     * @throws ThirdPartyException When there is an error confirming the user sign up
     */
    public function run(ConfirmUserSignUpRequest $request): void
    {
        try {
            $result = $this->cognitoClient->confirmUserSignUp(
                $request->email,
                $request->confirmation_code
            );
        } catch (UserNotFoundException $e) {
            throw new InvalidRequestException('User not found. The email may not be registered or may have been deleted.', 0, $e);
        } catch (InvalidCodeException $e) {
            throw new InvalidRequestException('Invalid or expired confirmation code. Please request a new code.', 0, $e);
        } catch (UserAlreadyConfirmedException $e) {
            throw new InvalidRequestException('This user is already confirmed and can sign in. No action needed.', 0, $e);
        } catch (Exception $e) {
            throw new ThirdPartyException('Error confirming user sign up.', 0, $e);
        }
    }
}
