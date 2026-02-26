<?php

namespace App\Containers\AppSection\Authentication\Actions;

use App\Containers\AppSection\Authorization\Enums\Role;
use App\Containers\AppSection\Authorization\Tasks\AddRoleToUserTask;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tasks\CreateUserTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use Illuminate\Auth\Events\Registered;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Ship\Parents\Exceptions\DBCallException;
use Illuminate\Auth\Access\AuthorizationException;

final class RegisterUserAction extends ParentAction
{
    public function __construct(
        private readonly CreateUserTask $createUserTask,
        private readonly AddRoleToUserTask $addRoleToUserTask,
    ) {
    }


    /**
     * Register a new user then trigger the registered event.
     *
     * @param UserDTO $data The data for the new user.
     * @return User The registered user.
     * @throws AuthorizationException If user registration is disabled.
     * @throws DBCallException If there is an error registering the user.
     */
    public function run(UserDTO $userDTO): User
    {
        if (!config('appSection-authentication.user_registration_enabled', false)) {
            throw new AuthorizationException("User registration is disabled");
        }
        try {
            Log::info('Registering user');
            Log::debug('User data: ' . json_encode($userDTO->getCreateArray()));
            $user = $this->createUserTask->run($userDTO);
            Log::info('User created: ' . $user->email);
            //trigger the registered event, this will trigger the email verification notification
            event(new Registered($user));
            Log::info('User registered: ' . $user->email);
            $user = $this->addRoleToUserTask->run($user, Role::USER);
            Log::info('User assigned role: ' . Role::USER->value);
            return $user;
        } catch (Exception $e) {
            Log::error('Error registering user: ' . $e->getMessage());
            throw new DBCallException("Error registering user", 0, $e);
        }
    }
}
