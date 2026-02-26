<?php

namespace App\Containers\AppSection\CognitoAuth\Actions;

use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\Authorization\Tasks\AddRoleToUserTask;
use App\Containers\AppSection\CognitoAuth\API\Requests\RegisterRequest;
use App\Containers\AppSection\CognitoAuth\Exceptions\UsernameExistsException;
use App\Containers\AppSection\CognitoAuth\Models\RegisterResponse;
use App\Containers\AppSection\CognitoAuth\Services\AWS\CognitoAuthClient;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\Tasks\CreateUserTask;
use App\Ship\Parents\Actions\Action as ParentAction;
use App\Ship\Parents\Exceptions\InvalidRequestException;

final class CognitoRegisterAction extends ParentAction
{
    public function __construct(
        private CognitoAuthClient $cognitoClient,
        private CreateUserTask $createUserTask,
        private AddRoleToUserTask $addRoleToUserTask,
    ) {}

    /**
     * Register a new user in the Cognito user pool (sign up) and create the user in the DB.
     * User must confirm sign-up via the code sent to their email unless already confirmed.
     *
     * @throws InvalidRequestException When email already exists in Cognito
     */
    public function run(RegisterRequest $request): RegisterResponse
    {
        $attributes = [
            'name' => $request->input('first_name') . ' ' . $request->input('last_name'),
            'email' => $request->input('email'),
        ];

        try {
            [$userConfirmed, $userCognitoId] = $this->cognitoClient->register(
                $request->email,
                $request->password,
                $attributes
            );
            
            $userDTO = $this->buildUserDTOFromRequest($request, $userCognitoId);
            $user = $this->createUserTask->run($userDTO);
            $this->addRoleToUserTask->run($user, $userDTO->role() ?? RoleEnum::USER);
            
        } catch (UsernameExistsException $e) {
            throw new InvalidRequestException('A user with this email already exists in Cognito. Please sign in or use a different email.', 0, $e);
        }
        $message = $userConfirmed
            ? 'User registered and confirmed.'
            : 'User registered. Please ask application admin to confirm the sign-up.';

        return new RegisterResponse($message, $userConfirmed);
    }

    private function buildUserDTOFromRequest(RegisterRequest $request, string $userCognitoId): UserDTO
    {
        return UserDTO::fromArray([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'role' => $request->input('role'),
            'cognito_id' => $userCognitoId,
        ]);
    }
}
