<?php

namespace App\Containers\AppSection\Authorization\API\Transformers;

use App\Containers\AppSection\Authorization\Models\Permission;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;
use League\Fractal\Resource\Collection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PermissionData',
    title: 'Permission Data',
    description: 'Permission data as returned by the transformer',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            description: 'Resource type',
            example: 'permissions'
        ),
        new OA\Property(
            property: 'id',
            type: 'string',
            description: 'Permission unique identifier',
            example: 'n9kq6345javb05je'
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            description: 'Permission name',
            example: 'manage-roles'
        ),
        new OA\Property(
            property: 'display_name',
            type: 'string',
            description: 'Permission display name',
            nullable: true,
            example: 'Manage Roles'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            description: 'Permission description',
            nullable: true,
            example: 'Create, Update, Delete, Get All, Attach/detach permissions to Roles and Get All Permissions.'
        )
    ]
)]
#[OA\Schema(
    schema: 'PermissionResource',
    title: 'Permission Resource',
    description: 'Permission resource representation in API responses',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/PermissionData'
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['roles']
                )
            ],
            type: 'object'
        )
    ]
)]
#[OA\Schema(
    schema: 'PermissionCollection',
    title: 'Permission Collection',
    description: 'Collection of permissions with pagination',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PermissionData')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['roles']
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
class PermissionTransformer extends ParentTransformer
{
    protected array $availableIncludes = [
        'roles',
    ];

    protected array $defaultIncludes = [];

    public function transform(Permission $permission): array
    {
        return [
            'type' => $permission->getResourceKey(),
            'id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'description' => $permission->description,
        ];
    }

    public function includeRoles(Permission $permission): Collection
    {
        return $this->collection($permission->roles, new RoleAdminTransformer());
    }
}
