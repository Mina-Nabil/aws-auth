<?php

namespace App\Containers\AppSection\Authentication\UI\API\Transformers;

use App\Containers\AppSection\Authentication\Data\DTOs\PasswordToken;
use App\Ship\Parents\Transformers\Transformer as ParentTransformer;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PasswordTokenData',
    title: 'Password Token Data',
    description: 'OAuth2 token data as returned by authentication endpoints',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            description: 'Resource type',
            example: 'password_tokens'
        ),
        new OA\Property(
            property: 'token_type',
            type: 'string',
            description: 'Token type',
            example: 'Bearer'
        ),
        new OA\Property(
            property: 'access_token',
            type: 'string',
            description: 'JWT access token',
            example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImY1ZGI...'
        ),
        new OA\Property(
            property: 'refresh_token',
            type: 'string',
            description: 'Refresh token for obtaining new access tokens',
            example: 'ZFDPA1S7H8Wydjkjl+xt+hPGWTagX+1PaGVucm3E...'
        ),
        new OA\Property(
            property: 'expires_in',
            type: 'integer',
            description: 'Token expiration time in seconds',
            example: 31536000
        )
    ]
)]
#[OA\Schema(
    schema: 'PasswordTokenResource',
    title: 'Password Token Resource',
    description: 'OAuth2 token resource representation in API responses',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/PasswordTokenData'
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(
                    property: 'include',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: []
                )
            ],
            type: 'object'
        )
    ]
)]
final class PasswordTokenTransformer extends ParentTransformer
{
    public function transform(PasswordToken $token): array
    {
        return [
            'type' => $token->getResourceKey(),
            'token_type' => $token->tokenType,
            'access_token' => $token->accessToken,
            'refresh_token' => $token->refreshToken->value(),
            'expires_in' => $token->expiresIn,
        ];
    }
}
