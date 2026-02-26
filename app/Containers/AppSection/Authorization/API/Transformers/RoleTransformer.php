<?php

namespace App\Containers\AppSection\Authorization\API\Transformers;

use App\Containers\AppSection\Authorization\Models\Role;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;
use League\Fractal\Resource\Collection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RoleData',
    title: 'Role Data',
    description: 'Role data as returned by the transformer',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            description: 'Resource type',
            example: 'roles'
        ),
        new OA\Property(
            property: 'id',
            type: 'string',
            description: 'Role unique identifier',
            example: 'qmv7dk48x5b690wx'
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            description: 'Role name',
            example: 'admin'
        ),
        new OA\Property(
            property: 'display_name',
            type: 'string',
            description: 'Role display name',
            nullable: true,
            example: 'Administrator Role'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            description: 'Role description',
            nullable: true,
            example: 'Administrator with full access'
        )
    ]
)]
#[OA\Schema(
    schema: 'RoleResource',
    title: 'Role Resource',
    description: 'Role resource representation in API responses',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/RoleData'
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['permissions']
                )
            ],
            type: 'object'
        )
    ]
)]
#[OA\Schema(
    schema: 'RoleCollection',
    title: 'Role Collection',
    description: 'Collection of roles with pagination',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/RoleData')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['permissions']
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
                        new OA\Property(property: 'total', type: 'integer', example: 4),
                        new OA\Property(property: 'count', type: 'integer', example: 4),
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
class RoleTransformer extends ParentTransformer
{
    protected array $availableIncludes = [
        'permissions',
    ];

    protected array $defaultIncludes = [];

    public function transform(Role $role): array
    {
        return [
            'type' => $role->getResourceKey(),
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'description' => $role->description,
        ];
    }

    public function includePermissions(Role $role): Collection
    {
        return $this->collection($role->permissions, new PermissionAdminTransformer());
    }
}
