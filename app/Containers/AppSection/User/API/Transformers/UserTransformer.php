<?php

namespace App\Containers\AppSection\User\API\Transformers;

use App\Containers\AppSection\Authorization\API\Transformers\PermissionTransformer;
use App\Containers\AppSection\Authorization\API\Transformers\RoleTransformer;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;
use League\Fractal\Resource\Collection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserData',
    title: 'User Data',
    description: 'User data as returned by the transformer',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            description: 'Resource type',
            example: 'users'
        ),
        new OA\Property(
            property: 'id',
            type: 'string',
            description: 'User unique identifier',
            format: 'uuid',
            example: '01234567-89ab-cdef-0123-456789abcdef'
        ),
        new OA\Property(
            property: 'first_name',
            type: 'string',
            description: 'User first name',
            example: 'John'
        ),
        new OA\Property(
            property: 'last_name',
            type: 'string',
            description: 'User last name',
            example: 'Doe'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            description: 'User email address',
            format: 'email',
            example: 'john.doe@example.com'
        ),
        new OA\Property(
            property: 'email_verified_at',
            type: 'string',
            description: 'Email verification timestamp',
            format: 'date-time',
            nullable: true,
            example: '2023-12-01T10:30:00Z'
        ),
        new OA\Property(
            property: 'is_disabled',
            type: 'boolean',
            description: 'Whether the user is disabled',
            example: false
        ),
        new OA\Property(
            property: 'cognito_id',
            type: 'string',
            description: 'Cognito user id',
            example: '1234567890'
        ),
        new OA\Property(
            property: 'is_cognito_user_confirmed',
            type: 'boolean',
            description: 'Whether the user is confirmed in Cognito',
            example: false
        )
    ]
)]
#[OA\Schema(
    schema: 'UserResource',
    title: 'User Resource',
    description: 'User resource representation in API responses',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/UserData'
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['roles', 'permissions']
                )
            ],
            type: 'object'
        )
    ]
)]
#[OA\Schema(
    schema: 'UserCollection',
    title: 'User Collection',
    description: 'Collection of users with pagination',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/UserData')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['roles', 'permissions']
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
class UserTransformer extends ParentTransformer
{
    protected array $availableIncludes = [
        'roles',
        'permissions',
    ];

    protected array $defaultIncludes = [];

    public function transform(User $user): array
    {
        return [
            'type' => $user->getResourceKey(),
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'is_disabled' => $user->is_disabled,
            'cognito_id' => $user->cognito_id,
            'is_cognito_user_confirmed' => $user->is_cognito_user_confirmed,
            'created_at' => $user->created_at->toISOString(),
        ];
    }

    public function includeRoles(User $user): Collection
    {
        return $this->collection($user->roles()->where('guard_name', 'api')->get(), new RoleTransformer());
    }

    public function includePermissions(User $user): Collection
    {
        return $this->collection($user->getAllPermissions()->where('guard_name', 'api'), new PermissionTransformer());
    }
}
