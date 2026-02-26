<?php

namespace App\Containers\AppSection\Authentication\UI\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authentication\Actions\RegisterUserAction;
use App\Containers\AppSection\Authentication\UI\API\Requests\RegisterUserRequest;
use App\Containers\AppSection\User\API\Transformers\UserTransformer;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Ship\Parents\Controllers\ApiController;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class RegisterUserController extends ApiController
{
    #[OA\Post(
        path: '/register',
        operationId: 'registerUser',
        summary: 'Register User',
        description: 'Register a new user account',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'email', 'password'],
            properties: [
                new OA\Property(
                    property: 'first_name',
                    type: 'string',
                    description: 'User first name',
                    example: 'John'
                ),
                new OA\Property(
                    property: 'last_name',
                    type: 'string',
                    description: 'User last name',
                    example: 'Doe'
                ),
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    description: 'User email address',
                    example: 'john.doe@example.com'
                ),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    format: 'password',
                    description: 'User password (minimum 8 characters)',
                    example: 'secretpassword'
                ),
                new OA\Property(
                    property: 'password_confirmation',
                    type: 'string',
                    format: 'password',
                    description: 'Password confirmation',
                    example: 'secretpassword'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'User registered successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'The given data was invalid.'
                ),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    example: ['email' => ['The email has already been taken.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function __invoke(RegisterUserRequest $request, RegisterUserAction $action): JsonResponse
    {
        try {

            $userDTO = UserDTO::fromArray($request->all());
            $user = $action->transactionalRun($userDTO);

            return Response::create($user, UserTransformer::class)->ok();
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }
}
