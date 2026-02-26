<?php 

namespace App\Containers\AppSection\CognitoAuth\API\Transformers;

use App\Containers\AppSection\CognitoAuth\Models\LoginResponse;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginResponse',
    title: 'Login Response',
    description: 'Login response',
    type: 'object',
    properties: [
        new OA\Property(property: 'access_token', type: 'string', example: 'access_token123'),
        new OA\Property(property: 'id_token', type: 'string', example: 'id_token123'),
        new OA\Property(property: 'refresh_token', type: 'string', example: 'refresh_token123'),
        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
    ]
)]
final class LoginResponseTransformer extends ParentTransformer
{
    public function transform(LoginResponse $loginResponse): array
    {
        return [
            'access_token' => $loginResponse->accessToken,
            'id_token' => $loginResponse->idToken,
            'refresh_token' => $loginResponse->refreshToken,
            'expires_in' => $loginResponse->expiresIn,
        ];
    }
}