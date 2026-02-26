<?php

namespace App\Containers\AppSection\User\API\Controllers;

use App\Containers\AppSection\User\API\Requests\ResendWelcomeEmailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Apiato\Support\Facades\Response;
use App\Ship\Parents\Controllers\ApiController;
use App\Containers\AppSection\User\Data\DTOs\UserDTO;
use App\Containers\AppSection\User\API\Requests\ListUsersRequest;
use App\Containers\AppSection\User\API\Requests\FindUserByIdRequest;
use App\Containers\AppSection\User\API\Requests\UpdatePasswordRequest;
use App\Containers\AppSection\User\API\Requests\UpdateStatusRequest;
use App\Containers\AppSection\User\API\Requests\CreateUserRequest;
use App\Containers\AppSection\User\API\Requests\UpdateUserRequest;
use App\Containers\AppSection\User\API\Requests\DeleteUserRequest;
use App\Containers\AppSection\User\Actions\CreateUserAction;
use App\Containers\AppSection\User\Actions\DeleteUserAction;
use App\Containers\AppSection\User\Actions\ListUsersAction;
use App\Containers\AppSection\User\Actions\UpdatePasswordAction;
use App\Containers\AppSection\User\Actions\UpdateStatusAction;
use App\Containers\AppSection\User\Actions\UpdateUserAction;
use App\Containers\AppSection\User\Actions\FindUserByIdAction;
use App\Containers\AppSection\User\Actions\ResendWelcomeEmailAction;
use App\Containers\AppSection\User\API\Transformers\UserTransformer;
use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Exceptions\ThirdPartyException;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Users', description: 'User management operations')]
final class UserCRUDController extends ApiController
{
    #[OA\Post(
        path: '/users',
        operationId: 'createUser',
        summary: 'Create a new user',
        description: 'Creates a new user with the provided information',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'email', 'role'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string', minLength: 2, maxLength: 50, example: 'John'),
                new OA\Property(property: 'last_name', type: 'string', minLength: 2, maxLength: 50, example: 'Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123', description: 'Password is required if application is not configured to require password reset'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123', description: 'Password confirmation is required if application is not configured to require password reset'),
                new OA\Property(property: 'role', type: 'string', enum: ['super_admin', 'admin', 'user'], example: 'user')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'User created successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function createUser(CreateUserRequest $request, CreateUserAction $action): JsonResponse
    {
        try {
            $userDTO = UserDTO::fromArray($request->all());
            $user = $action->run($userDTO);
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to create this user");
        } catch (InvalidRequestException $e) {
            return Response::badRequest($e->getMessage());
        } catch (DBCallException | ThirdPartyException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }

        return Response::create($user, UserTransformer::class)->created();
    }

    #[OA\Get(
        path: '/users/profile',
        operationId: 'getLoggedInUserProfile',
        summary: 'Get current user profile',
        description: 'Retrieves the authenticated user\'s profile information',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'include',
        description: 'Comma-separated list of relationships to include (roles,permissions)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'roles,permissions')
    )]
    #[OA\Response(
        response: 200,
        description: 'User profile retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function getLoggedInUserProfile(): JsonResponse
    {
        try {
            return Response::create(Auth::user(), UserTransformer::class)->ok();
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }

    #[OA\Get(
        path: '/users/{user_id}',
        operationId: 'findUserById',
        summary: 'Find user by ID',
        description: 'Retrieves a specific user by their unique identifier',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Parameter(
        name: 'include',
        description: 'Comma-separated list of relationships to include (roles,permissions)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'roles,permissions')
    )]
    #[OA\Response(
        response: 200,
        description: 'User found successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function findUserById(FindUserByIdRequest $request, FindUserByIdAction $action): JsonResponse
    {
        try {
            $user = $action->run($request->user_id);
            return Response::create($user, UserTransformer::class)->ok();
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to view this user");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }


    #[OA\Get(
        path: '/users',
        operationId: 'listUsers',
        summary: 'List all users',
        description: 'Retrieves a paginated list of all users in the system',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'page',
        description: 'Page number for pagination',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Number of items per page',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 10)
    )]
    #[OA\Parameter(
        name: 'include',
        description: 'Comma-separated list of relationships to include (roles,permissions)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'roles,permissions')
    )]
    #[OA\Response(
        response: 200,
        description: 'List of users retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserCollection')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function users(ListUsersRequest $request, ListUsersAction $action): JsonResponse
    {
        try {
            $users = $action->run();
            return Response::create($users, UserTransformer::class)->ok();
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to view all users");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }


    #[OA\Patch(
        path: '/users/{user_id}/is_disabled',
        operationId: 'updateUserStatus',
        summary: 'Update user status',
        description: 'Updates a user\'s enabled/disabled status',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['is_disabled'],
            properties: [
                new OA\Property(property: 'is_disabled', type: 'boolean', example: false, description: 'Whether the user should be disabled')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'User status updated successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function updateUserStatus(UpdateStatusRequest $request, UpdateStatusAction $action): JsonResponse
    {
        try {
            $userDTO = new UserDTO(
                userId: $request->user_id,
                isDisabled: $request->is_disabled,
            );
            $user = $action->run($userDTO);
        } catch (ResourceNotFound $e) {
            return Response::notFound("User not found");
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to update this user's status");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }

        return Response::create($user, UserTransformer::class)->ok();
    }


    #[OA\Patch(
        path: '/users/{user_id}/password',
        operationId: 'updateUserPassword',
        summary: 'Update user password',
        description: 'Updates a user\'s password',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['current_password', 'new_password', 'new_password_confirmation'],
            properties: [
                new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'currentpassword123'),
                new OA\Property(property: 'new_password', type: 'string', format: 'password', minLength: 8, example: 'newpassword123'),
                new OA\Property(property: 'new_password_confirmation', type: 'string', format: 'password', example: 'newpassword123')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Password updated successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function updateUserPassword(UpdatePasswordRequest $request, UpdatePasswordAction $action): JsonResponse
    {
        try {
            $userDTO = new UserDTO(
                userId: $request->user_id,
                password: $request->new_password,
                passwordResetRequired: false,
            );
            $user = $action->run($userDTO);
        } catch (ResourceNotFound $e) {
            return Response::notFound("User not found");
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to update this user's password");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }

        return Response::create($user, UserTransformer::class)->ok();
    }


    #[OA\Patch(
        path: '/users/{user_id}',
        operationId: 'updateUser',
        summary: 'Update user',
        description: 'Updates user information. All fields are optional. Updating a user\'s role requires the `sync-roles` permission, added to super admins only.',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'first_name', type: 'string', minLength: 2, maxLength: 50, example: 'John'),
                new OA\Property(property: 'last_name', type: 'string', minLength: 2, maxLength: 50, example: 'Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                new OA\Property(
                    property: 'role',
                    type: 'string',
                    enum: ['super_admin', 'admin', 'user'],
                    description: 'User role. Requires `update-any-user` permission to update.',
                    example: 'admin'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'User updated successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - User lacks required permissions')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function updateUserInfo(UpdateUserRequest $request, UpdateUserAction $action): JsonResponse
    {
        try {
            $userDTO = UserDTO::fromArray(array_merge($request->all(), ['user_id' => $request->user_id]));

            $user = $action->run($userDTO);

            return Response::create($user, UserTransformer::class)->ok();
        } catch (ResourceNotFound $e) {
            return Response::notFound("User not found");
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to update this user");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }


    #[OA\Delete(
        path: '/users/{user_id}',
        operationId: 'deleteUser',
        summary: 'Delete user',
        description: 'Permanently deletes a user from the system',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Response(response: 204, description: 'User deleted successfully')]
    #[OA\Response(response: 400, description: 'Bad request - Cannot delete user')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function deleteUser(DeleteUserRequest $request, DeleteUserAction $action): JsonResponse
    {

        try {
            $action->run($request->user_id);
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to delete this user");
        } catch (InvalidRequestException $e) {
            return Response::badRequest($e->getMessage());
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }

        return Response::noContent();
    }

    #[OA\Post(
        path: '/users/{user_id}/resend-welcome-email',
        operationId: 'resendWelcomeEmail',
        summary: 'Resend welcome email',
        description: 'Generates a new temporary password and resends the welcome email to the user',
        security: [['bearerAuth' => []]],
        tags: ['Users']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Response(
        response: 200,
        description: 'Welcome email resent successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function resendWelcomeEmail(ResendWelcomeEmailRequest $request, ResendWelcomeEmailAction $action): JsonResponse
    {
        try {
            $userDTO = new UserDTO(userId: $request->user_id);
            $user = $action->run($userDTO);
            return Response::create($user, UserTransformer::class)->ok();
        } catch (ResourceNotFound $e) {
            return Response::notFound("User not found");
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to resend welcome email for this user");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }
}
