<?php

namespace App\Containers\AppSection\CognitoAuth\API\Requests;

use App\Ship\Parents\Requests\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ConfirmUserSignUpRequest',
    title: 'Confirm User Sign Up Request',
    description: 'User confirms their sign-up with the confirmation code sent to their email.',
    required: ['email', 'confirmation_code'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'confirmation_code', type: 'string', example: '123456', description: 'Confirmation code sent to the user\'s email'),
    ]
)]
final class ConfirmUserSignUpRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'confirmation_code' => 'required|string',
        ];
    }
}
