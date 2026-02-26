<?php

namespace App\Containers\AppSection\CognitoAuth\API\Controllers;

use App\Containers\AppSection\CognitoAuth\Actions\CognitoConfirmSignUpAction;
use App\Containers\AppSection\CognitoAuth\Actions\CognitoConfirmUserSignUpAction;
use App\Containers\AppSection\CognitoAuth\Actions\CognitoRegisterAction;
use App\Containers\AppSection\CognitoAuth\API\Requests\AdminConfirmSignUpRequest;
use App\Containers\AppSection\CognitoAuth\API\Requests\ConfirmUserSignUpRequest;
use App\Containers\AppSection\CognitoAuth\API\Requests\RegisterRequest;
use App\Containers\AppSection\CognitoAuth\API\Requests\RejectCognitoConfirmationRequest;
use App\Containers\AppSection\CognitoAuth\API\Transformers\RegisterResponseTransformer;
use App\Containers\AppSection\User\Actions\DeleteUserAction;
use App\Containers\AppSection\User\Actions\ListUsersAction;
use App\Containers\AppSection\User\API\Requests\ListUsersRequest;
use App\Containers\AppSection\User\Tasks\FindUserByEmailTask;
use App\Containers\AppSection\User\API\Transformers\UserTransformer;
use App\Ship\Parents\Controllers\ApiController;
use App\Ship\Parents\Exceptions\DBCallException;
use App\Ship\Parents\Exceptions\InvalidRequestException;
use App\Ship\Parents\Exceptions\ThirdPartyException;
use Apiato\Support\Facades\Response;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class CognitoRegisterController extends ApiController
{
    #[OA\Get(
        path: '/cognito/register',
        operationId: 'listPendingCognitoConfirmationUsers',
        summary: 'List users awaiting Cognito confirmation',
        description: 'Retrieves a paginated list of users who registered via Cognito but are still waiting for admin confirmation',
        security: [['bearerAuth' => []]],
        tags: ['Cognito Auth']
    )]
    #[OA\Response(
        response: 200,
        description: 'List of users pending Cognito confirmation',
        content: new OA\JsonContent(ref: '#/components/schemas/UserCollection')
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function index(ListUsersRequest $request, ListUsersAction $action): JsonResponse
    {
        try {
            $users = $action->run(isCognitoUser: true, isCognitoUserConfirmed: false);
            return Response::create($users, UserTransformer::class)->ok();
        } catch (AuthorizationException $e) {
            return Response::forbidden('You are not authorized to view users pending Cognito confirmation');
        } catch (Exception $e) {
            report($e);
            return Response::serverError('Internal server error');
        }
    }

    #[OA\Post(
        path: '/cognito/register',
        operationId: 'cognitoRegister',
        summary: 'Register a new user in the Cognito user pool',
        description: 'Register a new user in the Cognito user pool. Admin user must confirm the sign-up manually.',
        tags: ['Cognito Auth']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')
    )]
    #[OA\Response(response: 200, description: 'Registration successful')]
    #[OA\Response(response: 422, description: 'Validation error or email already exists')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function store(RegisterRequest $request, CognitoRegisterAction $action)
    {
        try {
            $response = $action->run($request);
            return Response::create($response, RegisterResponseTransformer::class)->ok();
        } catch (InvalidRequestException $e) {
            return Response::json(['message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            report($e);
            return Response::serverError('Internal server error');
        }
    }

    #[OA\Post(
        path: '/cognito/register/confirm-user',
        operationId: 'cognitoConfirmUserSignUp',
        summary: 'Confirm User Sign Up',
        description: 'User confirms their sign-up with the confirmation code sent to their email. Public endpoint (no auth required).',
        tags: ['Cognito Auth']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ConfirmUserSignUpRequest')
    )]
    #[OA\Response(response: 200, description: 'Confirmation successful')]
    #[OA\Response(response: 422, description: 'Validation error or invalid/expired code')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function confirmUserSignUp(ConfirmUserSignUpRequest $request, CognitoConfirmUserSignUpAction $action)
    {
        try {
            $action->run($request);
            return Response::json(['message' => 'User confirmed successfully.']);
        } catch (InvalidRequestException $e) {
            return Response::json(['message' => $e->getMessage()], 422);
        } catch (ThirdPartyException $e) {
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError('Internal server error');
        }
    }

    #[OA\Put(
        path: '/cognito/register/confirm',
        operationId: 'cognitoAdminConfirmSignUp',
        summary: 'Admin Confirm Sign Up',
        description: 'Admin-confirm a user sign-up by email (no code). Requires confirm-cognito-sign-up permission (super-admin, admin).',
        security: [['bearerAuth' => []]],
        tags: ['Cognito Auth']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AdminConfirmSignUpRequest')
    )]
    #[OA\Response(response: 200, description: 'Confirmation successful')]
    #[OA\Response(response: 403, description: 'Forbidden - requires confirm-cognito-sign-up permission')]
    #[OA\Response(response: 422, description: 'Validation error or user not found / already confirmed')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function update(AdminConfirmSignUpRequest $request, CognitoConfirmSignUpAction $action)
    {
        try {
            $action->run($request);
            return Response::json(['message' => 'User confirmed successfully.']);
        } catch (InvalidRequestException $e) {
            return Response::json(['message' => $e->getMessage()], 422);
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError('Internal server error');
        }
    }

    #[OA\Delete(
        path: '/cognito/register',
        operationId: 'rejectCognitoConfirmation',
        summary: 'Reject Cognito confirmation',
        description: 'Rejects a pending Cognito sign-up and deletes the user from both Cognito and the database.',
        security: [['bearerAuth' => []]],
        tags: ['Cognito Auth']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RejectCognitoConfirmationRequest')
    )]
    #[OA\Response(response: 204, description: 'User rejected and deleted successfully')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 422, description: 'Validation error or user not found')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function destroy(RejectCognitoConfirmationRequest $request, DeleteUserAction $deleteUserAction, FindUserByEmailTask $findUserByEmailTask)
    {
        $user = $findUserByEmailTask->run($request->email);
        if (!$user) {
            return Response::json(['message' => 'User not found.'], 422);
        }
        if (!$user->cognito_id || $user->is_cognito_user_confirmed) {
            return Response::json(['message' => 'User is not pending Cognito confirmation.'], 422);
        }
        try {
            $deleteUserAction->run($user->id);
            return Response::noContent();
        } catch (InvalidRequestException $e) {
            return Response::json(['message' => $e->getMessage()], 422);
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError('Internal server error');
        }
    }
}
