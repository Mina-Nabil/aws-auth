<?php

namespace App\Containers\AppSection\Authorization\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authorization\API\Requests\CreateRoleRequest;
use App\Containers\AppSection\Authorization\API\Requests\ListRolesRequest;
use App\Containers\AppSection\Authorization\API\Requests\DeleteRoleRequest;
use App\Containers\AppSection\Authorization\API\Requests\FindRoleByIdRequest;
use App\Containers\AppSection\Authorization\API\Transformers\RoleAdminTransformer;
use App\Containers\AppSection\Authorization\Actions\CreateRoleAction;
use App\Containers\AppSection\Authorization\Actions\DeleteRoleAction;
use App\Containers\AppSection\Authorization\Actions\ListRolesAction;
use App\Containers\AppSection\Authorization\Actions\FindRoleByIdAction;
use App\Ship\Parents\Controllers\ApiController;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class RoleCRUDController extends ApiController
{
    #[OA\Get(
        path: '/roles',
        operationId: 'listRoles',
        summary: 'List all roles',
        description: 'Retrieve a paginated list of all roles in the system',
        security: [['bearerAuth' => []]],
        tags: ['Roles']
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
        description: 'Roles retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/RoleAdminCollection')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(ListRolesRequest $request, ListRolesAction $action): JsonResponse
    {
        try {
            $roles = $action->run();

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

    #[OA\Get(
        path: '/roles/{role_id}',
        operationId: 'findRoleById',
        summary: 'Find a role by ID',
        description: 'Retrieve a specific role by its unique identifier',
        security: [['bearerAuth' => []]],
        tags: ['Roles']
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
        schema: new OA\Schema(type: 'string', example: 'permissions')
    )]
    #[OA\Response(
        response: 200,
        description: 'Role retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/RoleAdminResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'Role not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function show(FindRoleByIdRequest $request, FindRoleByIdAction $action): JsonResponse
    {
        try {
            $role = $action->run($request->role_id);

            return Response::create($role, RoleAdminTransformer::class)->ok();
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
        path: '/roles',
        operationId: 'createRole',
        summary: 'Create a role',
        description: 'Create a new role in the system',
        security: [['bearerAuth' => []]],
        tags: ['Roles']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(
                    property: 'name',
                    type: 'string',
                    description: 'Unique role name',
                    example: 'editor'
                ),
                new OA\Property(
                    property: 'description',
                    type: 'string',
                    description: 'Role description',
                    nullable: true,
                    example: 'Content editor with publish permissions'
                ),
                new OA\Property(
                    property: 'display_name',
                    type: 'string',
                    description: 'Human-readable role name',
                    nullable: true,
                    example: 'Content Editor'
                ),
                new OA\Property(
                    property: 'guard_name',
                    type: 'string',
                    description: 'Guard name for the role',
                    enum: ['api', 'web'],
                    example: 'api'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Role created successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/RoleAdminResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
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
                    example: ['name' => ['The name has already been taken.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function create(CreateRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        try {
            $role = $action->run(
                $request->input('name'),
                $request->input('description'),
                $request->input('display_name'),
            );

            return Response::create($role, RoleAdminTransformer::class)->created();
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
        path: '/roles/{role_id}',
        operationId: 'deleteRole',
        summary: 'Delete a role',
        description: 'Delete a role by its unique identifier',
        security: [['bearerAuth' => []]],
        tags: ['Roles']
    )]
    #[OA\Parameter(
        name: 'role_id',
        description: 'Role unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'qmv7dk48x5b690wx')
    )]
    #[OA\Response(
        response: 204,
        description: 'Role deleted successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - insufficient permissions')]
    #[OA\Response(response: 404, description: 'Role not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function destroy(DeleteRoleRequest $request, DeleteRoleAction $action): JsonResponse
    {
        try {
            $action->run($request->role_id);

            return Response::noContent();
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
