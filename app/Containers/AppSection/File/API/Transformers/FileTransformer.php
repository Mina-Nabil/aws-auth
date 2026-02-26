<?php

namespace App\Containers\AppSection\File\API\Transformers;

use Apiato\Http\Resources\Item;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\User\API\Transformers\UserTransformer;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;
use League\Fractal\Resource\Collection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FileData',
    title: 'File Data',
    description: 'File data as returned by the transformer',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'string',
            description: 'File unique identifier',
            example: '9d4e5f6a-7b8c-9d0e-1f2a-3b4c5d6e7f8a'
        ),
        new OA\Property(
            property: 'original_name',
            type: 'string',
            description: 'Original filename',
            example: 'document.pdf'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            description: 'File description',
            nullable: true,
            example: 'Important project document'
        ),
        new OA\Property(
            property: 'tags',
            type: 'array',
            description: 'File tags',
            items: new OA\Items(type: 'string'),
            nullable: true,
            example: ['project', 'important']
        ),
        new OA\Property(
            property: 'visibility',
            type: 'string',
            description: 'File visibility level',
            enum: ['public', 'private', 'share_link'],
            example: 'private'
        ),
        new OA\Property(
            property: 'storage_disk',
            type: 'string',
            description: 'Storage disk name',
            example: 'local'
        ),
        new OA\Property(
            property: 'storage_path',
            type: 'string',
            description: 'File path on storage disk',
            example: 'files/2025/10/document.pdf'
        ),
        new OA\Property(
            property: 'size_bytes',
            type: 'integer',
            description: 'File size in bytes',
            example: 1048576
        ),
        new OA\Property(
            property: 'mime_type',
            type: 'string',
            description: 'File MIME type',
            example: 'application/pdf'
        ),
        new OA\Property(
            property: 'checksum',
            type: 'string',
            description: 'File checksum',
            nullable: true,
            example: 'a1b2c3d4e5f6'
        ),
        new OA\Property(
            property: 'uploaded_by',
            type: 'string',
            description: 'ID of user who uploaded the file',
            example: '7c2d3e4f-5a6b-7c8d-9e0f-1a2b3c4d5e6f'
        )
    ]
)]
#[OA\Schema(
    schema: 'FileResource',
    title: 'File Resource',
    description: 'File resource representation in API responses',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/FileData'
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['entities']
                )
            ],
            type: 'object'
        )
    ]
)]
#[OA\Schema(
    schema: 'FileCollection',
    title: 'File Collection',
    description: 'Collection of uploaded files with pagination',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/FileData')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['entities']
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 100),
                        new OA\Property(property: 'count', type: 'integer', example: 10),
                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'total_pages', type: 'integer', example: 10),
                        new OA\Property(
                            property: 'links',
                            type: 'object',
                            example: []
                        )
                    ],
                    type: 'object'
                )
            ],
            type: 'object'
        )
    ]
)]
class FileTransformer extends ParentTransformer
{
    protected array $availableIncludes = [
        'entities', 'uploaded_by',
    ];

    protected array $defaultIncludes = [];

    public function transform(File $File): array
    {
        return [
            'id' => $File->id,
            'original_name' => $File->original_name,
            'description' => $File->description,
            'tags' => $File->tags,
            'visibility' => $File->visibility,
            'storage_disk' => $File->storage_disk,
            'storage_path' => $File->storage_path,
            'size_bytes' => $File->size_bytes,
            'mime_type' => $File->mime_type,
            'checksum' => $File->checksum,
            'file_url' => $File->getSignedUrl(),
            'uploaded_by' => $File->uploaded_by,
        ];
    }

    public function includeEntities(File $File): Collection
    {
        return $this->collection($File->entityLinks()->get(), new FileLinkTransformer());
    }

    public function includeUploadedBy(File $File): Item
    {
        return $this->item($File->uploadedBy, new UserTransformer());
    }
}
