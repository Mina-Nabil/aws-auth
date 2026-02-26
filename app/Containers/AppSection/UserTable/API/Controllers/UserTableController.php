<?php

namespace App\Containers\AppSection\UserTable\API\Controllers;

use App\Containers\AppSection\UserTable\API\Requests\GetUserTableRequest;
use App\Containers\AppSection\UserTable\API\Requests\CreateOrUpdateUserTableRequest;
use App\Containers\AppSection\UserTable\API\Requests\DeleteUserTableRequest;
use App\Containers\AppSection\UserTable\Actions\ListUserTablesAction;
use App\Containers\AppSection\UserTable\Actions\CreateOrUpdateUserTableAction;
use App\Containers\AppSection\UserTable\Actions\DeleteUserTableAction;
use App\Containers\AppSection\UserTable\Data\DTOs\UserTableDTO;
use App\Containers\AppSection\UserTable\API\Transformers\UserTableTransformer;
use App\Ship\Parents\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Apiato\Support\Facades\Response;
use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use Illuminate\Auth\Access\AuthorizationException;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'User Preferences', description: 'User table preferences management endpoints')]
final class UserTableController extends ApiController
{
    #[OA\Get(
        path: '/users/me/preferences/table-lists/{table_name}',
        operationId: 'getUserTablePreferences',
        summary: 'Get user table preferences',
        description: 'Retrieves the visible columns preference for a specific table for the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['User Preferences']
    )]
    #[OA\Parameter(
        name: 'table_name',
        description: 'The name of the table',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'jobs')
    )]
    #[OA\Response(
        response: 200,
        description: 'User table preferences retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'visible_columns',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['job_number', 'customer', 'status']
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Table preferences not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function show(GetUserTableRequest $request, ListUserTablesAction $action): JsonResponse
    {
        try {
            $tableName = $request->route('table_name');
            $userTables = $action->run($tableName);

            if ($userTables->isEmpty()) {
                return Response::notFound("Table preferences not found for table: {$tableName}");
            }

            return Response::create($userTables->first(), UserTableTransformer::class)->ok();
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to view table preferences");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }

    #[OA\Put(
        path: '/users/me/preferences/table-lists/{table_name}',
        operationId: 'createOrUpdateUserTablePreferences',
        summary: 'Create or update user table preferences',
        description: 'Creates or updates the visible columns preference for a specific table for the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['User Preferences']
    )]
    #[OA\Parameter(
        name: 'table_name',
        description: 'The name of the table',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'jobs')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['visible_columns'],
            properties: [
                new OA\Property(
                    property: 'visible_columns',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['job_number', 'status', 'engineer']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'User table preferences updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'visible_columns',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['job_number', 'status', 'engineer']
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'User table preferences created successfully')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function store(CreateOrUpdateUserTableRequest $request, CreateOrUpdateUserTableAction $action): JsonResponse
    {
        try {
            /** @var \App\Containers\AppSection\User\Models\User $user */
            $user = Auth::user();
            $tableName = $request->route('table_name');

            $userTableDTO = UserTableDTO::fromArray([
                'user_id' => $user->id,
                'table_name' => $tableName,
                'columns' => $request->input('visible_columns'),
            ]);

            $userTable = $action->run($userTableDTO);

            return Response::create($userTable, UserTableTransformer::class)->ok();
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to update table preferences");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }

    #[OA\Delete(
        path: '/users/me/preferences/table-lists/{table_name}',
        operationId: 'deleteUserTablePreferences',
        summary: 'Delete user table preferences',
        description: 'Deletes the visible columns preference for a specific table for the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['User Preferences']
    )]
    #[OA\Parameter(
        name: 'table_name',
        description: 'The name of the table',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'jobs')
    )]
    #[OA\Response(response: 204, description: 'User table preferences deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Table preferences not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function destroy(DeleteUserTableRequest $request, DeleteUserTableAction $action): JsonResponse
    {
        try {
            $tableName = $request->route('table_name');
            $action->run($tableName);
            return Response::noContent();
        } catch (ResourceNotFound $e) {
            $tableName = $request->route('table_name');
            return Response::notFound("Table preferences not found for table: {$tableName}");
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to delete table preferences");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }
}
