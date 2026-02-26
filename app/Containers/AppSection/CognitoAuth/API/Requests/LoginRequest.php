<?php 

namespace App\Containers\AppSection\CognitoAuth\API\Requests;

use App\Ship\Parents\Requests\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    title: 'Login Request',
    description: 'Login request',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
    ]
)]
final class LoginRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];

    }
}