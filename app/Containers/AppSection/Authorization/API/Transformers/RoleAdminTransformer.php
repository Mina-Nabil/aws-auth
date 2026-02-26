<?php

namespace App\Containers\AppSection\Authorization\API\Transformers;

use App\Containers\AppSection\Authorization\Models\Role;
use Illuminate\Support\Arr;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RoleAdminData',
    title: 'Role Admin Data',
    description: 'Role data with admin fields as returned by the admin transformer',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/RoleData'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'guard_name',
                    type: 'string',
                    description: 'Role guard name',
                    example: 'api'
                )
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'RoleAdminResource',
    title: 'Role Admin Resource',
    description: 'Role admin resource representation in API responses',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/RoleAdminData'
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
    schema: 'RoleAdminCollection',
    title: 'Role Admin Collection',
    description: 'Collection of roles with admin fields and pagination',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/RoleAdminData')
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
final class RoleAdminTransformer extends RoleTransformer
{
    public function transform(Role $role): array
    {
        return Arr::add(
            parent::transform($role),
            'guard_name',
            $role->guard_name,
        );
    }
}
