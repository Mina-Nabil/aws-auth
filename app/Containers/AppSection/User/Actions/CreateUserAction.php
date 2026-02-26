<?php

namespace App\Containers\AppSection\User\Actions;

use App\Containers\AppSection\Authorization\Tasks\AddRoleToUserTask;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Enums\UserGate;
use App\Containers\AppSection\User\Exceptions\CognitoUsernameExistsException;
use App\Containers\AppSection\User\Models\CognitoUser;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Services\CognitoSyncClient;
use App\Containers\AppSection\User\Tasks\CreateUserTask;
use App\Containers\AppSection\User\Tasks\GenerateTemporaryPasswordTask;
use App\Containers\AppSection\User\Tasks\SendWelcomeEmailTask;
use App\Containers\AppSection\User\Tasks\SetUserCognitoInfoTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\ThirdPartyException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;


class CreateUserAction extends ParentAction
{
    private bool $bypassGate = false;
    private bool $sendWelcomeEmail = true;
    private bool $setTemporaryPassword = true;
    private bool $createCognitoUserIfConfigActive = true;

    public function __construct(
        private readonly CreateUserTask $createUserTask,
        private readonly AddRoleToUserTask $addRoleToUserTask,
        private readonly GenerateTemporaryPasswordTask $generateTemporaryPasswordTask,
        private readonly SendWelcomeEmailTask $sendWelcomeEmailTask,
        private readonly CognitoSyncClient $cognitoSyncClient,
        private readonly SetUserCognitoInfoTask $setUserCognitoInfoTask,
    ) {}

    public function bypassGate(): self
    {
        $this->bypassGate = true;

        return $this;
    }

    public function skipWelcomeEmail(): self
    {
        $this->sendWelcomeEmail = false;

        return $this;
    }

    public function skipCognitoUserCreation(): self
    {
        $this->createCognitoUserIfConfigActive = false;

        return $this;
    }

    public function skipTemporaryPassword(): self
    {
        $this->setTemporaryPassword = false;

        return $this;
    }

    /**
     * Create a new admin user.
     *
     * @param array $data The data for the new admin user.
     * @return User The created admin user.
     * @throws ThirdPartyException If there is an error creating the user in AWS Cognito.
     * @throws DBCallException If there is an error creating the admin user in the DB.
     * @throws AuthorizationException If the user is not authorized to create a user.
     */
    public function run(UserDTO $userDTO): User
    {
        if (!$this->bypassGate) {
            Gate::authorize(UserGate::CREATE_USER);
        } else {
            Log::debug('Bypassing gate');
        }

        $temporaryPassword = null;
        if ($this->setTemporaryPassword && config('appSection-authorization.require_pw_reset')) {
            $temporaryPassword = $this->generateTemporaryPasswordTask->run();
            $userDTO = $userDTO->withTemporaryPassword($temporaryPassword);
        }

        Log::debug('Raw Create User Data: ' . json_encode($userDTO->toArray()));

        try {
            $user = DB::transaction(function () use ($userDTO) {

                $user = $this->createUserTask->run($userDTO);

                Log::info('User created: ' . $user->email);

                $user = $this->addRoleToUserTask->run($user, $userDTO->role());

                $user->markEmailAsVerified();

                if ($this->createCognitoUserIfConfigActive && config('cognito-auth.sync_user_pool_active')) {
                    $cognitoUser = $this->createCognitoUser($user, $userDTO->password);
                    if (!$cognitoUser) {
                        throw new ThirdPartyException("Unable to create user in AWS Cognito");
                    }
                    $this->setUserCognitoInfoTask->run($user, $cognitoUser);
                }
                return $user;
            });

            if ($this->sendWelcomeEmail) {
                $this->sendWelcomeEmailTask->run($user, $temporaryPassword);
            }

            return $user;
        } catch (ThirdPartyException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DBCallException("Error creating user", 0, $e);
        }
    }

    /**
     * Create a new user or fetching already existing user in the AWS Cognito user pool.
     *
     * @param User $user The user to create in the AWS Cognito user pool.
     * @param string $password The password for the user.
     * @return CognitoUser The created user in the AWS Cognito user pool.
     * @throws ThirdPartyException If there is an error creating the user in AWS Cognito.
     */
    private function createCognitoUser(User $user, string $password): CognitoUser|null
    {
        try {

            $cognitoUser = $this->cognitoSyncClient->createUser($user->email, $password, [
                'email' => $user->email,
                'email_verified' => 'true',
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ]);
            Log::info('User created in AWS Cognito: ' . $user->email);
            return $cognitoUser;
        } catch (CognitoUsernameExistsException $e) {
            try {
                $cognitoUser = $this->cognitoSyncClient->getUser($user->email);
                Log::info('User already exists in AWS Cognito');
                return $cognitoUser;
            } catch (CognitoIdentityProviderException $e) {
                throw new ThirdPartyException("Error fetching user info from AWS Cognito", 0, $e);
            } catch (Exception $e) {
                Log::error('Error fetching user info from AWS Cognito: ' . $e->getMessage());
                throw new ThirdPartyException("Error creating user in AWS Cognito", 0, $e);
            }
        } catch (Exception $e) {
            Log::error('Error creating user in AWS Cognito: ' . $e->getMessage());
            throw new ThirdPartyException("Error creating user in AWS Cognito", 0, $e);
        }
    }
}
