<?php

namespace App\Containers\AppSection\Authorization\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authorization\API\Requests\ListUserRolesRequest;
use App\Containers\AppSection\Authorization\API\Transformers\RoleAdminTransformer;
use App\Containers\AppSection\Authorization\API\Requests\SyncUserRolesRequest;
use App\Containers\AppSection\Authorization\API\Requests\AssignRolesToUserRequest;
use App\Containers\AppSection\Authorization\API\Requests\RemoveUserRolesRequest;
use App\Containers\AppSection\Authorization\Actions\AssignRolesToUserAction;
use App\Containers\AppSection\Authorization\Actions\ListUserRolesAction;
use App\Containers\AppSection\Authorization\Actions\SyncUserRolesAction;
use App\Containers\AppSection\Authorization\Actions\RemoveUserRolesAction;
use App\Containers\AppSection\User\API\Transformers\UserTransformer;
use App\Ship\Parents\Controllers\ApiController;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class UserRolesCRUDController extends ApiController
{
    #[OA\Get(
        path: '/users/{user_id}/roles',
        operationId: 'listUserRoles',
        summary: 'List user roles',
        description: 'Retrieve roles assigned to a specific user',
        security: [['bearerAuth' => []]],
        tags: ['User Roles']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Parameter(
        name: 'include',
        description: 'Comma-separated list of relationships to include',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'permissions')
    )]
    #[OA\Response(
        response: 200,
        description: 'User roles retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/RoleAdminCollection')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(ListUserRolesRequest $request, ListUserRolesAction $action): JsonResponse
    {
        try {
            $roles = $action->run($request->user_id);

            return Response::create($roles, RoleAdminTransformer::class)->ok();
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

    #[OA\Patch(
        path: '/users/{user_id}/roles',
        operationId: 'assignRolesToUser',
        summary: 'Assign roles to user',
        description: 'Assign new roles to a specific user',
        security: [['bearerAuth' => []]],
        tags: ['User Roles']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['role_ids'],
            properties: [
                new OA\Property(
                    property: 'role_ids',
                    type: 'array',
                    description: 'Array of role IDs to assign',
                    items: new OA\Items(type: 'string'),
                    example: ['qmv7dk48x5b690wx', 'bml0wd39b5pkznag']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Roles assigned successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'User not found')]
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
                    example: ['role_ids' => ['One or more role IDs are invalid.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function assign(AssignRolesToUserRequest $request, AssignRolesToUserAction $action): JsonResponse
    {
        try {
            $user = $action->run($request->user_id, ...$request->role_ids);

            return Response::create($user, UserTransformer::class)
                ->parseIncludes(['roles'])
                ->ok();
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

    #[OA\Put(
        path: '/users/{user_id}/roles',
        operationId: 'syncUserRoles',
        summary: 'Sync user roles',
        description: 'Synchronize roles for a specific user (replaces all current roles)',
        security: [['bearerAuth' => []]],
        tags: ['User Roles']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['role_ids'],
            properties: [
                new OA\Property(
                    property: 'role_ids',
                    type: 'array',
                    description: 'Array of role IDs to sync (replaces all current roles)',
                    items: new OA\Items(type: 'string'),
                    example: ['qmv7dk48x5b690wx', 'bml0wd39b5pkznag']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Roles synchronized successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'User not found')]
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
                    example: ['role_ids' => ['One or more role IDs are invalid.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function sync(SyncUserRolesRequest $request, SyncUserRolesAction $action): JsonResponse
    {
        try {
            $user = $action->run($request->user_id, ...$request->role_ids);

            return Response::create($user, UserTransformer::class)
                ->parseIncludes(['roles'])
                ->ok();
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

    #[OA\Delete(
        path: '/users/{user_id}/roles',
        operationId: 'removeUserRoles',
        summary: 'Remove user roles',
        description: 'Remove existing roles from a specific user',
        security: [['bearerAuth' => []]],
        tags: ['User Roles']
    )]
    #[OA\Parameter(
        name: 'user_id',
        description: 'User unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['role_ids'],
            properties: [
                new OA\Property(
                    property: 'role_ids',
                    type: 'array',
                    description: 'Array of role IDs to remove',
                    items: new OA\Items(type: 'string'),
                    example: ['qmv7dk48x5b690wx', 'bml0wd39b5pkznag']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Roles removed successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'User not found')]
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
                    example: ['role_ids' => ['One or more role IDs are invalid.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function destroy(RemoveUserRolesRequest $request, RemoveUserRolesAction $action): JsonResponse
    {
        try {
            $user = $action->run($request->user_id, ...$request->role_ids);

            return Response::create($user, UserTransformer::class)
                ->parseIncludes(['roles'])
                ->ok();
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
