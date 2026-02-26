<?php

namespace App\Containers\AppSection\Authentication\UI\API\Controllers\WebClient;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authentication\Actions\API\WebClient\RefreshTokenAction;
use App\Containers\AppSection\Authentication\UI\API\Requests\WebClient\RefreshTokenRequest;
use App\Containers\AppSection\Authentication\UI\API\Transformers\PasswordTokenTransformer;
use App\Containers\AppSection\Authentication\Values\RefreshToken;
use App\Ship\Parents\Controllers\ApiController;
use Exception;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class RefreshTokenController extends ApiController
{
    #[OA\Post(
        path: '/clients/refresh',
        operationId: 'refreshToken',
        summary: 'Refresh Token',
        description: 'Get new access and refresh tokens using a valid refresh token',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['refresh_token'],
            properties: [
                new OA\Property(
                    property: 'refresh_token',
                    type: 'string',
                    description: 'Valid refresh token',
                    example: 'ZFDPA1S7H8Wydjkjl+xt+hPGWTagX+1PaGVucm3E...'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Token refreshed successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/PasswordTokenResource')
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized - invalid or expired refresh token',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Invalid refresh token')
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function __invoke(RefreshTokenRequest $request, RefreshTokenAction $action): JsonResponse
    {
        try {

            $result = $action->run(RefreshToken::createFrom($request));
            return Response::create($result, PasswordTokenTransformer::class)->ok()
            ->withCookie($result->refreshToken->asCookie());

        } catch (Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }
}
