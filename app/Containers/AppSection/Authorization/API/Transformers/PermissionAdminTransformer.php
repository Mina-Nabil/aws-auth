<?php

namespace App\Containers\AppSection\Authorization\API\Transformers;

use App\Containers\AppSection\Authorization\Models\Permission;
use Illuminate\Support\Arr;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PermissionAdminData',
    title: 'Permission Admin Data',
    description: 'Permission data with admin fields as returned by the admin transformer',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PermissionData'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'guard_name',
                    type: 'string',
                    description: 'Permission guard name',
                    example: 'api'
                )
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'PermissionAdminResource',
    title: 'Permission Admin Resource',
    description: 'Permission admin resource representation in API responses',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/PermissionAdminData'
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: []
                )
            ],
            type: 'object'
        )
    ]
)]
#[OA\Schema(
    schema: 'PermissionAdminCollection',
    title: 'Permission Admin Collection',
    description: 'Collection of permissions with admin fields and pagination',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PermissionAdminData')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: []
                ),
                new OA\Property(
                    property: 'custom',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: []
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 13),
                        new OA\Property(property: 'count', type: 'integer', example: 13),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'total_pages', type: 'integer', example: 1),
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
final class PermissionAdminTransformer extends PermissionTransformer
{
    protected array $availableIncludes = [];

    public function transform(Permission $permission): array
    {
        return Arr::add(
            parent::transform($permission),
            'guard_name',
            $permission->guard_name,
        );
    }
}
