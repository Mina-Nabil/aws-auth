<?php

namespace App\Ship\Parents\Controllers;

use Apiato\Core\Controllers\ApiController as AbstractApiController;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Box APP API',
    version: '1.0',
    description: 'Box APP API Documentation'
)]
#[OA\Server(url: '/api/v1', description: 'API Server v1')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    name: 'Authorization',
    in: 'header',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'Error Response',
    description: 'Standard error response format',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            description: 'Error message',
            example: 'The given data was invalid.'
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            description: 'Validation errors (when applicable)',
            nullable: true,
            example: [
                'email' => ['The email field is required.'],
                'password' => ['The password field is required.']
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    title: 'Validation Error Response',
    description: 'Validation error response format',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            description: 'Error message',
            example: 'The given data was invalid.'
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            description: 'Field-specific validation errors',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: [
                'email' => ['The email field is required.'],
                'password' => ['The password must be at least 8 characters.']
            ]
        )
    ]
)]
abstract class ApiController extends AbstractApiController
{
}
