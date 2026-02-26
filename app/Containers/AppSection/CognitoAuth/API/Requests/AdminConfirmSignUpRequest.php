<?php

namespace App\Containers\AppSection\CognitoAuth\API\Requests;

use App\Containers\AppSection\Authorization\Enums\Role as RoleEnum;
use App\Containers\AppSection\CognitoAuth\Enums\CognitoAuthGate;
use App\Ship\Parents\Requests\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AdminConfirmSignUpRequest',
    title: 'Admin Confirm Sign Up Request',
    description: 'Admin confirm a user sign-up by email (no code required). Requires confirm-cognito-sign-up permission (super-admin, admin).',
    required: ['email', 'role'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['super-admin', 'admin', 'user'], example: 'user', description: 'Role to assign to the user after confirmation'),
    ]
)]
final class AdminConfirmSignUpRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'role' => 'nullable|string|in:' . implode(',', array_map(fn($r) => $r->value, RoleEnum::ROLES_ARRAY)),
        ];
    }
}
