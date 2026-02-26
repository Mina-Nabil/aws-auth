<?php

namespace App\Containers\AppSection\Authentication\UI\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authentication\Actions\API\RevokeTokenAction;
use App\Containers\AppSection\Authentication\UI\API\Requests\RevokeTokenRequest;
use App\Ship\Parents\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Exception;

final class RevokeTokenController extends ApiController
{
    #[OA\Post(
        path: '/logout',
        operationId: 'logout',
        summary: 'Logout',
        description: 'Revoke the current user\'s access token and logout',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(
        response: 202,
        description: 'Token revoked successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Token revoked successfully.'
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function __invoke(RevokeTokenRequest $request, RevokeTokenAction $action): JsonResponse
    {
        try {
            $cookies = $action->run($request->user());

            return Response::accepted([
                'message' => 'Token revoked successfully.',
            ])->withCookie($cookies);
        } catch (Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }
}
