<?php

namespace App\Containers\AppSection\CognitoAuth\API\Requests;

use App\Ship\Parents\Requests\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RejectCognitoConfirmationRequest',
    title: 'Reject Cognito Confirmation Request',
    description: 'Reject a pending Cognito sign-up and delete the user from Cognito and the database.',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
    ]
)]
final class RejectCognitoConfirmationRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }
}
