<?php

namespace App\Containers\AppSection\File\API\Controllers;

use Illuminate\Http\JsonResponse;
use Apiato\Support\Facades\Response;
use App\Containers\AppSection\File\Actions\UpdateFileAction;
use App\Ship\Parents\Controllers\ApiController;
use App\Containers\AppSection\File\API\Requests\DeleteFileRequest;
use App\Containers\AppSection\File\API\Requests\ListFilesRequest;
use App\Containers\AppSection\File\API\Requests\UploadFileRequest;
use App\Containers\AppSection\File\API\Requests\UpdateFileRequest;
use App\Containers\AppSection\File\API\Transformers\FileTransformer;
use App\Containers\AppSection\File\Data\DTOs\FileDTO;
use App\Containers\AppSection\File\API\Requests\ShowFileRequest;
use App\Containers\AppSection\File\Actions\FindFileByIdAction;
use App\Containers\AppSection\File\Actions\CreateFileAction;
use App\Containers\AppSection\File\Actions\DeleteFileAction;
use App\Containers\AppSection\File\Actions\ListFilesAction;
use App\Ship\Parents\Exceptions\DBCallException;
use Apiato\Core\Repositories\Exceptions\ResourceNotFound;
use App\Containers\AppSection\File\Enums\FileType;
use App\Ship\Parents\Exceptions\FileManagementException;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use OpenApi\Attributes as OA;
use Exception;
use Illuminate\Http\Response as HttpResponse;

#[OA\Tag(name: 'Files', description: 'Files upload endpoints')]
final class FileCRUDController extends ApiController
{
    #[OA\Get(
        path: '/files',
        operationId: 'listFiles',
        summary: 'List all files',
        description: 'Retrieves a paginated and filterable list of all files in the system',
        security: [['bearerAuth' => []]],
        tags: ['Files']
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
        name: 'q',
        description: 'Search query to filter files by name or description',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', maxLength: 255, example: 'project document')
    )]
    #[OA\Parameter(
        name: 'tags',
        description: 'Filter files by tags (comma-separated), the endpoint will return files that contain any of the tags',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', maxLength: 255, example: 'project,important')
    )]
    #[OA\Parameter(
        name: 'file_type',
        description: 'Filter files by file type, one of: image, video, archive, pdf, document',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'pdf')
    )]
    #[OA\Parameter(
        name: 'uploader_id',
        description: 'Filter files by uploader user ID',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Parameter(
        name: 'entity_type',
        description: 'Filter files by associated entity type',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', maxLength: 255, example: 'project')
    )]
    #[OA\Parameter(
        name: 'entity_id',
        description: 'Filter files by associated entity ID',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Parameter(
        name: 'from',
        description: 'Filter files uploaded after this date',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')
    )]
    #[OA\Parameter(
        name: 'to',
        description: 'Filter files uploaded before this date',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'date', example: '2024-12-31')
    )]
    #[OA\Parameter(
        name: 'include',
        description: 'Comma-separated list of relationships to include, available options: uploaded_by, entities',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'uploaded_by')
    )]
    #[OA\Response(
        response: 200,
        description: 'List of files retrieved successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/FileCollection')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to view files')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(ListFilesRequest $request, ListFilesAction $action): JsonResponse
    {
        $q = $request->query('q');
        $tags = $request->query('tags');
        $fileType = $request->query('file_type') ? FileType::tryFrom($request->query('file_type')) : null;
        $uploaderId = $request->query('uploader_id');
        $entityType = $request->query('entity_type');
        $entityId = $request->query('entity_id');
        $afterDate = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $beforeDate = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        try {
            $files = $action->run($q, $tags, $fileType, $uploaderId, $entityType, $entityId, $afterDate, $beforeDate);
            return Response::create($files, FileTransformer::class)->ok();
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to view files");
        } catch (DBCallException | Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }

    #[OA\Get(
        path: '/files/{file_id}',
        operationId: 'findFileById',
        summary: 'Find file by ID',
        description: 'Retrieves a specific file by its unique identifier',
        security: [['bearerAuth' => []]],
        tags: ['Files']
    )]
    #[OA\Parameter(
        name: 'file_id',
        description: 'File unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Parameter(
        name: 'include',
        description: 'Comma-separated list of relationships to include, available options: uploaded_by, entities',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'uploaded_by')
    )]
    #[OA\Response(
        response: 200,
        description: 'File found successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/FileResource')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to view this file')]
    #[OA\Response(response: 404, description: 'File not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function show(ShowFileRequest $request, FindFileByIdAction $action, $url_only = false): JsonResponse
    {
        try {
            $file = $action->run($request->file_id);
            if ($url_only === true) {
                return Response::json(["url" => $file->getSignedUrl()], HttpResponse::HTTP_OK);
            } else {
                return Response::create($file, FileTransformer::class)->ok();
            }
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to view this file");
        } catch (DBCallException | Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }

    #[OA\Get(
        path: '/files/{file_id}/download',
        operationId: 'downloadFile',
        summary: 'Download file by ID',
        description: 'Retrieves a signed/temporary url for the file',
        security: [['bearerAuth' => []]],
        tags: ['Files']
    )]
    #[OA\Parameter(
        name: 'file_id',
        description: 'File unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Response(
        response: 200,
        description: 'File download URL generated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'url',
                    type: 'string',
                    description: 'Temporary download URL for the file',
                    example: 'https://example.com/storage/files/document.pdf?signature=...'
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to view this file')]
    #[OA\Response(response: 404, description: 'File not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function download(ShowFileRequest $request, FindFileByIdAction $action): JsonResponse
    {
        return $this->show($request, $action, true);
    }

    #[OA\Post(
        path: '/files',
        operationId: 'uploadFile',
        summary: 'Upload a new file',
        description: 'Uploads a new file with metadata and returns the file information',
        security: [['bearerAuth' => []]],
        tags: ['Files']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file', 'visibility', 'uploaded_by'],
                properties: [
                    new OA\Property(
                        property: 'file',
                        type: 'string',
                        format: 'binary',
                        description: 'File to upload (max size: config appSection-f
                        ile.max_file_size, allowed types: pdf, images, office documents, archives)'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        maxLength: 255,
                        nullable: true,
                        description: 'Description of the file',
                        example: 'Important project document'
                    ),
                    new OA\Property(
                        property: 'tags',
                        type: 'array',
                        items: new OA\Items(type: 'string', maxLength: 255),
                        nullable: true,
                        description: 'Array of tags for the file. In multipart/form-data, send each tag separately using tags[] notation (e.g., tags[]=project&tags[]=important)',
                        example: ['project', 'important', 'text']
                    ),
                    new OA\Property(
                        property: 'visibility',
                        type: 'string',
                        enum: ['public', 'private', 'share_link'],
                        description: 'File visibility level',
                        example: 'private'
                    ),
                    new OA\Property(
                        property: 'checksum',
                        type: 'string',
                        maxLength: 255,
                        nullable: true,
                        description: 'Optional file checksum for verification',
                        example: 'a1b2c3d4e5f6'
                    ),
                    new OA\Property(
                        property: 'uploaded_by',
                        type: 'string',
                        format: 'uuid',
                        description: 'UUID of the user uploading the file',
                        example: '7c2d3e4f-5a6b-7c8d-9e0f-1a2b3c4d5e6f'
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'File uploaded successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/FileResource')
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to upload files')]
    #[OA\Response(response: 422, description: 'Validation error - Invalid file type or size')]
    #[OA\Response(response: 500, description: 'Internal server error - File upload failed')]
    public function create(UploadFileRequest $request, CreateFileAction $action): JsonResponse
    {
        try {
            $fileDTO = FileDTO::fromArray($request->all());
            $file = $action->run($fileDTO);
            return Response::create($file, FileTransformer::class)->created();
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (FileManagementException | DBCallException | Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }


    #[OA\Patch(
        path: '/files/{file_id}',
        operationId: 'updateFile',
        summary: 'Update file',
        description: 'Updates file information (metadata only, not the file itself)',
        security: [['bearerAuth' => []]],
        tags: ['Files']
    )]
    #[OA\Parameter(
        name: 'file_id',
        description: 'File unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['visibility'],
            properties: [
                new OA\Property(
                    property: 'description',
                    type: 'string',
                    maxLength: 255,
                    nullable: true,
                    description: 'Updated description of the file',
                    example: 'Updated project document'
                ),
                new OA\Property(
                    property: 'tags',
                    type: 'array',
                    items: new OA\Items(type: 'string', maxLength: 255),
                    nullable: true,
                    description: 'Updated array of tags for the file',
                    example: ['project', 'updated', 'important']
                ),
                new OA\Property(
                    property: 'visibility',
                    type: 'string',
                    enum: ['public', 'private', 'share_link'],
                    description: 'Updated file visibility level',
                    example: 'public'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'File updated successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/FileResource')
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to update this file')]
    #[OA\Response(response: 404, description: 'File not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function update(UpdateFileRequest $request, UpdateFileAction $action): JsonResponse
    {
        try {
            $fileDTO = FileDTO::fromArray(array_merge($request->all(), ['file_id' => $request->file_id]));
            $file = $action->run($fileDTO);
            return Response::create($file, FileTransformer::class)->ok();
        } catch (ResourceNotFound $e) {
            return Response::notFound("File not found");
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to update this file");
        } catch (DBCallException | Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }


    #[OA\Delete(
        path: '/files/{file_id}',
        operationId: 'deleteFile',
        summary: 'Delete file',
        description: 'Permanently deletes a file from the system',
        security: [['bearerAuth' => []]],
        tags: ['Files']
    )]
    #[OA\Parameter(
        name: 'file_id',
        description: 'File unique identifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Response(response: 204, description: 'File deleted successfully')]
    #[OA\Response(response: 400, description: 'Bad request - Cannot delete file')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden - Not authorized to delete this file')]
    #[OA\Response(response: 404, description: 'File not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function destroy(DeleteFileRequest $request, DeleteFileAction $action): JsonResponse
    {
        try {
            $action->run($request->file_id);
            return Response::noContent();
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (DBCallException | Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }
}
