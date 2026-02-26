<?php

namespace App\Containers\AppSection\CognitoAuth\API\Controllers;

use App\Containers\AppSection\CognitoAuth\API\Requests\LoginRequest;
use App\Containers\AppSection\CognitoAuth\Actions\CognitoLoginAction;
use App\Containers\AppSection\CognitoAuth\Actions\CognitoLogoutAction;
use App\Containers\AppSection\CognitoAuth\API\Transformers\LoginResponseTransformer;
use App\Ship\Parents\Controllers\ApiController;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use Apiato\Support\Facades\Response;
use App\Containers\AppSection\CognitoAuth\Actions\CognitoRefreshTokenAction;
use App\Ship\Parents\Exceptions\ThirdPartyException;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CognitoLoginController extends ApiController
{
    #[OA\Post(
        path: '/cognito/login',
        operationId: 'cognitoLogin',
        summary: 'Cognito Login',
        description: 'Login using email and password saved in the AWS Cognito user pool',
        tags: ['Cognito Auth']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Login successful',
        content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(LoginRequest $request, CognitoLoginAction $action)
    {
        try {
            $response = $action->run($request);
        } catch (InvalidRequestException $e) {
            return Response::unauthorized($e->getMessage());
        } catch (ThirdPartyException $e) {
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError('Internal server error');
        }
        return Response::create($response, LoginResponseTransformer::class)->ok();
    }

    #[OA\Post(
        path: '/cognito/refresh-token',
        operationId: 'cognitoRefreshToken',
        summary: 'Cognito Refresh Token',
        description: 'Refresh the user\'s access token',
        tags: ['Cognito Auth']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['refresh_token'],
            properties: [
                new OA\Property(property: 'refresh_token', type: 'string', example: 'refresh_token123'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Refresh token successful',
        content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function refreshToken(Request $request, CognitoRefreshTokenAction $action)
    {
        try {
            $response = $action->run($request->refresh_token);
        } catch (InvalidRequestException $e) {
            return Response::unauthorized($e->getMessage());
        } catch (ThirdPartyException $e) {
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError('Internal server error');
        }
        return Response::create($response, LoginResponseTransformer::class)->ok();
    }


    #[OA\Post(
        path: '/cognito/logout',
        operationId: 'cognitoLogout',
        summary: 'Cognito Logout',
        description: 'Sign out the user from Cognito (invalidates tokens)',
        tags: ['Cognito Auth']
    )]
    #[OA\Response(response: 202, description: 'Logout successful')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function destroy(Request $request, CognitoLogoutAction $action)
    {
        try {
            $action->run($request);
        } catch (Exception $e) {
            report($e);
            return Response::serverError('Internal server error');
        }
        return Response::accepted([
            'message' => 'Logout successful',
        ]);
    }
}
