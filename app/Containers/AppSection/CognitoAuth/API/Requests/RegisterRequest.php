<?php

namespace App\Containers\AppSection\CognitoAuth\API\Requests;

use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Ship\Parents\Requests\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    title: 'Register Request',
    description: 'Cognito sign-up request',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePass123!'),
        new OA\Property(property: 'first_name', type: 'string', example: 'John', description: 'First name'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe', description: 'Last name'),
        new OA\Property(property: 'role', type: 'string', enum: ['super-admin', 'admin', 'user'], example: 'user', description: 'Role to assign. Defaults to user.'),
    ]
)]
final class RegisterRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role' => 'nullable|string|in:' . implode(',', array_map(fn ($r) => $r->value, RoleEnum::ROLES_ARRAY)),
        ];
    }
}
