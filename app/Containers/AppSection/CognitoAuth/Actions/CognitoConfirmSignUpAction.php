<?php

namespace App\Containers\AppSection\CognitoAuth\Actions;

use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\Authorization\Tasks\SyncUserRolesTask;
use App\Containers\AppSection\CognitoAuth\API\Requests\AdminConfirmSignUpRequest;
use App\Containers\AppSection\CognitoAuth\Enums\CognitoAuthGate;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserAlreadyConfirmedException;
use App\Containers\AppSection\CognitoAuth\Exceptions\UserNotFoundException;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoAuthClient;
use App\Containers\AppSection\User\Tasks\FindUserByEmailTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use App\Ship\Parents\Exceptions\ThirdPartyException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CognitoConfirmSignUpAction extends ParentAction
{
    public function __construct(
        private CognitoAuthClient $cognitoClient,
        private FindUserByEmailTask $findUserByEmailTask,
        private SyncUserRolesTask $syncUserRolesTask,
    ) {}

    /**
     * Admin-confirm a user's sign-up (no confirmation code) and assign the requested role in the DB.
     * Requires confirm-cognito-sign-up permission.
     *
     * @throws InvalidRequestException When user is not found in Cognito or is already confirmed
     * @throws AuthorizationException When the user does not have the confirm-cognito-sign-up permission
     * @throws ThirdPartyException When there is an error confirming the user sign up
     */
    public function run(AdminConfirmSignUpRequest $request): void
    {
        Gate::authorize(CognitoAuthGate::CONFIRM_SIGN_UP);

        try {
            $user = $this->findUserByEmailTask->run($request->email);
        } catch (Exception $e) {
            throw new DBCallException('Can\'t find user by email.', 0, $e);
        }

        if (!$user) {
            throw new InvalidRequestException('User not found. The email may not be registered or may have been deleted.');
        }

        try {
            DB::transaction(function () use ($user, $request) {
                if ($request->filled('role')) {
                    $role = RoleEnum::from($request->role);
                    $this->syncUserRolesTask->run($user, [$role]);
                }
                $this->cognitoClient->confirmSignUp($request->email);
            });
        } catch (UserNotFoundException $e) {
            throw new InvalidRequestException('User not found in Cognito. The email may not be registered or may have been deleted.', 0, $e);
        } catch (UserAlreadyConfirmedException $e) {
            throw new InvalidRequestException('This user is already confirmed and can sign in. No action needed.', 0, $e);
        } catch (Exception $e) {
            throw new ThirdPartyException('Error confirming user sign up.', 0, $e);
        }
    }
}
