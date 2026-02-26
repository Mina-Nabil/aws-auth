<?php

namespace App\Containers\AppSection\Authorization\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Ship\Parents\Controllers\ApiController;
use App\Containers\AppSection\Authorization\API\Requests\GivePermissionsToRoleRequest;
use App\Containers\AppSection\Authorization\API\Requests\ListRolePermissionsRequest;
use App\Containers\AppSection\Authorization\API\Requests\SyncRolePermissionsRequest;
use App\Containers\AppSection\Authorization\API\Requests\RevokeRolePermissionsRequest;
use App\Containers\AppSection\Authorization\API\Transformers\RoleAdminTransformer;
use App\Containers\AppSection\Authorization\Actions\ListRolePermissionsAction;
use App\Containers\AppSection\Authorization\Actions\GivePermissionsToRoleAction;
use App\Containers\AppSection\Authorization\Actions\RevokeRolePermissionsAction;
use App\Containers\AppSection\Authorization\Actions\SyncRolePermissionsAction;
use App\Containers\AppSection\Authorization\API\Transformers\PermissionAdminTransformer;
use App\Ship\Parents\Exceptions\DBCallException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Exception;

final class RolePermissionsCRUDController extends ApiController
{
    #[OA\Get(
        path: '/roles/{role_id}/permissions',
        operationId: 'listRolePermissions',
        summary: 'List role permissions',
        description: 'Retrieve permissions assigned to a specific role',
        security: [['bearerAuth' => []]],
        tags: ['Role Permissions']
    )]
    #[OA\Parameter(
        name: 'role_id',
        description: 'Role unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'qmv7dk48x5b690wx')
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
        description: 'Role permissions retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/PermissionAdminCollection')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'Role not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(ListRolePermissionsRequest $request, ListRolePermissionsAction $action): JsonResponse
    {
        try {
            $permissions = $action->run($request->role_id);

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
        path: '/roles/{role_id}/permissions',
        operationId: 'givePermissionsToRole',
        summary: 'Attach permissions to role',
        description: 'Attach new permissions to a specific role',
        security: [['bearerAuth' => []]],
        tags: ['Role Permissions']
    )]
    #[OA\Parameter(
        name: 'role_id',
        description: 'Role unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'qmv7dk48x5b690wx')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['permission_ids'],
            properties: [
                new OA\Property(
                    property: 'permission_ids',
                    type: 'array',
                    description: 'Array of permission IDs to attach',
                    items: new OA\Items(type: 'string'),
                    example: ['n9kq6345javb05je', 'qmv7dk48x5b690wx']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Permissions attached successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/RoleAdminResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'Role not found')]
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
    public function assign(GivePermissionsToRoleRequest $request, GivePermissionsToRoleAction $action): JsonResponse
    {
        try {
            $role = $action->run($request->role_id, ...$request->permission_ids);

            return Response::create($role, RoleAdminTransformer::class)
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

    #[OA\Put(
        path: '/roles/{role_id}/permissions',
        operationId: 'syncRolePermissions',
        summary: 'Sync role permissions',
        description: 'Synchronize permissions for a specific role (replaces all current permissions)',
        security: [['bearerAuth' => []]],
        tags: ['Role Permissions']
    )]
    #[OA\Parameter(
        name: 'role_id',
        description: 'Role unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'qmv7dk48x5b690wx')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['permission_ids'],
            properties: [
                new OA\Property(
                    property: 'permission_ids',
                    type: 'array',
                    description: 'Array of permission IDs to sync (replaces all current permissions)',
                    items: new OA\Items(type: 'string'),
                    example: ['n9kq6345javb05je', 'qmv7dk48x5b690wx']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Permissions synchronized successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/RoleAdminResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'Role not found')]
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
    public function sync(SyncRolePermissionsRequest $request, SyncRolePermissionsAction $action): JsonResponse
    {
        try {
            $role = $action->run($request->role_id, ...$request->permission_ids);

            return Response::create($role, RoleAdminTransformer::class)
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
        path: '/roles/{role_id}/permissions',
        operationId: 'revokeRolePermissions',
        summary: 'Revoke role permissions',
        description: 'Remove permissions from a specific role',
        security: [['bearerAuth' => []]],
        tags: ['Role Permissions']
    )]
    #[OA\Parameter(
        name: 'role_id',
        description: 'Role unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'qmv7dk48x5b690wx')
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
        content: new OA\JsonContent(ref: '#/components/schemas/RoleAdminResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'Role not found')]
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
    public function destroy(RevokeRolePermissionsRequest $request, RevokeRolePermissionsAction $action): JsonResponse
    {
        try {
            $role = $action->run($request->role_id, ...$request->permission_ids);

            return Response::create($role, RoleAdminTransformer::class)
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
