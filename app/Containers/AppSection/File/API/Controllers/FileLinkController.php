<?php

namespace App\Containers\AppSection\File\API\Controllers;

use Illuminate\Http\JsonResponse;
use Apiato\Support\Facades\Response;
use App\Ship\Parents\Controllers\ApiController;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Containers\AppSection\File\Actions\AttachFileAction;
use App\Containers\AppSection\File\Actions\DetachFileAction;
use App\Containers\AppSection\File\API\Requests\AttachFileRequest;
use App\Containers\AppSection\File\API\Requests\DetachFileRequest;
use Illuminate\Auth\Access\AuthorizationException;
use OpenApi\Attributes as OA;
use Exception;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Containers\AppSection\File\Actions\ListEntityFilesAction;
use App\Containers\AppSection\File\API\Requests\ListEntityFilesRequest;
use App\Containers\AppSection\File\API\Transformers\FileTransformer;

#[OA\Tag(name: 'Files', description: 'Files upload endpoints')]
final class FileLinkController extends ApiController
{
    #[OA\Get(
        path: '/{entity_type}/{entity_id}/files',
        operationId: 'listEntityFiles',
        summary: 'List files attached to an entity',
        description: 'Retrieves all files attached to a specific entity (e.g., user, job, etc.)',
        security: [['bearerAuth' => []]],
        tags: ['Files']
    )]
    #[OA\Parameter(
        name: 'entity_type',
        description: 'Type of entity (e.g., user, job)',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'user')
    )]
    #[OA\Parameter(
        name: 'entity_id',
        description: 'Unique identifier of the entity',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '7c2d3e4f-5a6b-7c8d-9e0f-1a2b3c4d5e6f')
    )]
    #[OA\Parameter(
        name: 'type',
        description: 'Type of files to list one of: image, video, archive, pdf, document',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'image')
    )]
    #[OA\Parameter(
        name: 'include',
        description: 'Comma-separated list of relationships to include (entities, uploaded_by)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'uploaded_by')
    )]
    #[OA\Response(
        response: 200,
        description: 'Files retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/FileCollection')
    )]
    #[OA\Response(response: 400, description: 'Bad request - Invalid request data')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to view files for this entity')]
    #[OA\Response(response: 422, description: 'Validation error - Invalid entity type or ID')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(ListEntityFilesRequest $request, ListEntityFilesAction $action): JsonResponse
    {
        try {
            $files = $action->run($request->entity_type, $request->entity_id, $request->type);
            return Response::create($files, FileTransformer::class)->ok();
        } catch (ModelNotFoundException $e) {
            return Response::notFound("Entity not found");
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to view files");
        } catch (InvalidRequestException $e) {
            return Response::badRequest($e->getMessage());
        } catch (DBCallException | Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/{entity_type}/{entity_id}/files',
        operationId: 'attachFileToEntity',
        summary: 'Attach a file to an entity',
        description: 'Links an existing file to a specific entity (e.g., user, job, etc.)',
        security: [['bearerAuth' => []]],
        tags: ['Files']
    )]
    #[OA\Parameter(
        name: 'entity_type',
        description: 'Type of entity (e.g., user, job)',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'user')
    )]
    #[OA\Parameter(
        name: 'entity_id',
        description: 'Unique identifier of the entity',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '7c2d3e4f-5a6b-7c8d-9e0f-1a2b3c4d5e6f')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['file_id'],
            properties: [
                new OA\Property(
                    property: 'file_id',
                    type: 'string',
                    format: 'uuid',
                    description: 'UUID of the file to attach',
                    example: '9d4e5f6a-7b8c-9d0e-1f2a-3b4c5d6e7f8a'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'File attached successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'File attached successfully')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Bad request - Invalid request data')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to attach files to this entity')]
    #[OA\Response(response: 404, description: 'Not found - File or entity not found')]
    #[OA\Response(response: 422, description: 'Validation error - Invalid entity type, entity ID, or file ID')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function create(AttachFileRequest $request, AttachFileAction $action): JsonResponse
    {
        try {
            $action->run($request->entity_type, $request->entity_id, $request->file_id);
            return Response::created("File attached successfully");
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (InvalidRequestException $e) {
            return Response::badRequest($e->getMessage());
        } catch (ModelNotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (DBCallException | Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }

    #[OA\Delete(
        path: '/{entity_type}/{entity_id}/files/{file_id}',
        operationId: 'detachFileFromEntity',
        summary: 'Detach a file from an entity',
        description: 'Removes the link between a file and a specific entity (e.g., user, job, etc.). The file itself is not deleted.',
        security: [['bearerAuth' => []]],
        tags: ['Files']
    )]
    #[OA\Parameter(
        name: 'entity_type',
        description: 'Type of entity (e.g., user, job)',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'user')
    )]
    #[OA\Parameter(
        name: 'entity_id',
        description: 'Unique identifier of the entity',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '7c2d3e4f-5a6b-7c8d-9e0f-1a2b3c4d5e6f')
    )]
    #[OA\Parameter(
        name: 'file_id',
        description: 'UUID of the file to detach',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '9d4e5f6a-7b8c-9d0e-1f2a-3b4c5d6e7f8a')
    )]
    #[OA\Response(
        response: 204,
        description: 'File detached successfully (no content)'
    )]
    #[OA\Response(response: 400, description: 'Bad request - Invalid request data')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to detach files from this entity')]
    #[OA\Response(response: 404, description: 'Not found - File or entity not found')]
    #[OA\Response(response: 422, description: 'Validation error - Invalid entity type, entity ID, or file ID')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function destroy(DetachFileRequest $request, DetachFileAction $action): JsonResponse
    {
        try {
            $action->run($request->entity_type, $request->entity_id, $request->file_id);
            return Response::noContent();
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (InvalidRequestException $e) {
            return Response::badRequest($e->getMessage());
        } catch (ModelNotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (DBCallException | Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }
}
