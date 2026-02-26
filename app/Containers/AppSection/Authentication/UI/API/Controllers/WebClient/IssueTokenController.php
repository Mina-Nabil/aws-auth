<?php

namespace App\Containers\AppSection\Authentication\UI\API\Controllers\WebClient;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authentication\Actions\API\WebClient\IssueTokenAction;
use App\Containers\AppSection\Authentication\Exceptions\LoginFailed;
use App\Containers\AppSection\Authentication\UI\API\Requests\WebClient\IssueTokenRequest;
use App\Containers\AppSection\Authentication\UI\API\Transformers\PasswordTokenTransformer;
use App\Containers\AppSection\Authentication\Values\UserCredential;
use App\Ship\Parents\Controllers\ApiController;
use Exception;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class IssueTokenController extends ApiController
{
    #[OA\Post(
        path: '/clients/login',
        operationId: 'login',
        summary: 'Login',
        description: 'Authenticate user using email and password to issue access token',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    description: 'User email address',
                    example: 'user@example.com'
                ),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    format: 'password',
                    description: 'User password',
                    example: 'secretpassword'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Login successful - access token issued',
        content: new OA\JsonContent(ref: '#/components/schemas/PasswordTokenResource')
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized - invalid credentials',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Login failed')
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function __invoke(IssueTokenRequest $request, IssueTokenAction $action): JsonResponse
    {
        try {

            $result = $action->run(
                UserCredential::createFrom($request),
            );
        } catch (LoginFailed $e) {
            return Response::unauthorized($e->getMessage());
        } catch (Exception $e) {
            return Response::serverError($e->getMessage());
        }

        return Response::create($result, PasswordTokenTransformer::class)->ok()
            ->withCookie($result->refreshToken->asCookie());
    }
}
