<?php

namespace App\Containers\AppSection\Authorization\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authorization\Actions\ListUserPermissionsAction;
use App\Containers\AppSection\Authorization\API\Requests\ListUserPermissionsRequest;
use App\Containers\AppSection\Authorization\API\Transformers\PermissionAdminTransformer;
use App\Containers\AppSection\Authorization\API\Requests\RevokeUserPermissionsRequest;
use App\Containers\AppSection\Authorization\Actions\RevokeUserPermissionsAction;
use App\Containers\AppSection\Authorization\API\Requests\GivePermissionsToUserRequest;
use App\Containers\AppSection\Authorization\Actions\GivePermissionsToUserAction;
use App\Containers\AppSection\User\API\Transformers\UserTransformer;
use App\Ship\Parents\Controllers\ApiController;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class UserPermissionsCRUDController extends ApiController
{
    #[OA\Get(
        path: '/users/{user_id}/permissions',
        operationId: 'listUserPermissions',
        summary: 'List user permissions',
        description: 'Retrieve permissions assigned to a specific user',
        security: [['bearerAuth' => []]],
        tags: ['User Permissions']
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
        schema: new OA\Schema(type: 'string', example: 'roles')
    )]
    #[OA\Response(
        response: 200,
        description: 'User permissions retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/PermissionAdminCollection')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(ListUserPermissionsRequest $request, ListUserPermissionsAction $action): JsonResponse
    {
        try {
            $permissions = $action->run($request->user_id);

            return Response::create($permissions, PermissionAdminTransformer::class)->ok();
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

    #[OA\Post(
        path: '/users/{user_id}/permissions',
        operationId: 'givePermissionsToUser',
        summary: 'Attach permissions to user',
        description: 'Attach direct permissions to a specific user',
        security: [['bearerAuth' => []]],
        tags: ['User Permissions']
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
            required: ['permission_ids'],
            properties: [
                new OA\Property(
                    property: 'permission_ids',
                    type: 'array',
                    description: 'Array of permission IDs to assign',
                    items: new OA\Items(type: 'string'),
                    example: ['n9kq6345javb05je', 'qmv7dk48x5b690wx']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Permissions attached successfully',
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
                    example: ['permission_ids' => ['One or more permission IDs are invalid.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function assign(GivePermissionsToUserRequest $request, GivePermissionsToUserAction $action): JsonResponse
    {
        try {
            $user = $action->run($request->user_id, ...$request->permission_ids);

            return Response::create($user, UserTransformer::class)
                ->parseIncludes(['permissions'])
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
        path: '/users/{user_id}/permissions',
        operationId: 'revokeUserPermissions',
        summary: 'Revoke user permissions',
        description: 'Remove permissions from a specific user',
        security: [['bearerAuth' => []]],
        tags: ['User Permissions']
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
            required: ['permission_ids'],
            properties: [
                new OA\Property(
                    property: 'permission_ids',
                    type: 'array',
                    description: 'Array of permission IDs to revoke',
                    items: new OA\Items(type: 'string'),
                    example: ['n9kq6345javb05je', 'qmv7dk48x5b690wx']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Permissions revoked successfully',
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
                    example: ['permission_ids' => ['One or more permission IDs are invalid.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function destroy(RevokeUserPermissionsRequest $request, RevokeUserPermissionsAction $action): JsonResponse
    {
        try {
            $user = $action->run($request->user_id, ...$request->permission_ids);

            return Response::create($user, UserTransformer::class)
                ->parseIncludes(['permissions'])
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
