<?php

namespace App\Containers\AppSection\File\API\Transformers;

use App\Containers\AppSection\File\Models\FileLink;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FileLinkData',
    title: 'FileLink Data',
    description: 'File link data as returned by the transformer',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'file_id',
            type: 'string',
            description: 'File unique identifier',
            example: '9d4e5f6a-7b8c-9d0e-1f2a-3b4c5d6e7f8a'
        ),
        new OA\Property(
            property: 'entity_id',
            type: 'string',
            description: 'Entity unique identifier that the file is linked to',
            example: '8c3d4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f'
        ),
        new OA\Property(
            property: 'entity_type',
            type: 'string',
            description: 'Type of entity the file is linked to',
            example: 'job'
        )
    ]
)]
#[OA\Schema(
    schema: 'FileLinkResource',
    title: 'FileLink Resource',
    description: 'FileLink resource representation in API responses',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/FileLinkData'
        ),
        new OA\Property(
            property: 'meta',
            properties: [],
            type: 'object'
        )
    ]
)]
#[OA\Schema(
    schema: 'FileLinkCollection',
    title: 'FileLink Collection',
    description: 'Collection of entities info linked to a file with pagination',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/FileLinkData')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
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
class FileLinkTransformer extends ParentTransformer
{
    public function transform(FileLink $FileLink): array
    {
        return [
            'file_id' => $FileLink->file_id,
            'entity_id' => $FileLink->entity_id,
            'entity_type' => $FileLink->entity_type,
        ];
    }
}
