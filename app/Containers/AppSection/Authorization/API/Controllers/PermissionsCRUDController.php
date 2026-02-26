<?php

namespace App\Containers\AppSection\Authorization\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authorization\Actions\ListPermissionsAction;
use App\Containers\AppSection\Authorization\API\Requests\ListPermissionsRequest;
use App\Containers\AppSection\Authorization\API\Transformers\PermissionAdminTransformer;
use App\Ship\Parents\Controllers\ApiController;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\Authorization\API\Requests\FindPermissionByIdRequest;
use App\Containers\AppSection\Authorization\Actions\FindPermissionByIdAction;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class PermissionsCRUDController extends ApiController
{
    #[OA\Get(
        path: '/permissions',
        operationId: 'listPermissions',
        summary: 'List all permissions',
        description: 'Retrieve a paginated list of all permissions in the system',
        security: [['bearerAuth' => []]],
        tags: ['Permissions']
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
        description: 'Permissions retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/PermissionAdminCollection')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(ListPermissionsRequest $request, ListPermissionsAction $action): JsonResponse
    {
        try {
            $permissions = $action->run();

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

    #[OA\Get(
        path: '/permissions/{permission_id}',
        operationId: 'findPermissionById',
        summary: 'Find a permission by ID',
        description: 'Retrieve a specific permission by its unique identifier',
        security: [['bearerAuth' => []]],
        tags: ['Permissions']
    )]
    #[OA\Parameter(
        name: 'permission_id',
        description: 'Permission unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'n9kq6345javb05je')
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
        description: 'Permission retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/PermissionAdminResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'Permission not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function show(FindPermissionByIdRequest $request, FindPermissionByIdAction $action): JsonResponse
    {
        try {
            $permission = $action->run($request->permission_id);

            return Response::create($permission, PermissionAdminTransformer::class)->ok();
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
