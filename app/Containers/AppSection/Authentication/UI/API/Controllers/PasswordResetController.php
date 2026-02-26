<?php

namespace App\Containers\AppSection\Authentication\UI\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authentication\Actions\PasswordReset\ForgotPasswordAction;
use App\Containers\AppSection\Authentication\Actions\PasswordReset\ResetPasswordAction;
use App\Containers\AppSection\Authentication\UI\API\Requests\PasswordReset\ForgotPasswordRequest;
use App\Containers\AppSection\Authentication\UI\API\Requests\PasswordReset\ResetPasswordRequest;
use App\Ship\Parents\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Exception;

final class PasswordResetController extends ApiController
{
    #[OA\Post(
        path: '/forgot-password',
        operationId: 'forgotPassword',
        summary: 'Forgot Password',
        description: 'Send password reset link to user email address',
        tags: ['Authentication', 'Password Reset']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    description: 'User email address',
                    example: 'user@example.com'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 202,
        description: 'Password reset link sent successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'We have emailed your password reset link!'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'The given data was invalid.'
                ),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    example: ['email' => ['We can\'t find a user with that email address.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        try {
            $status = $action->run($request);
            return Response::accepted($status);
        } catch (Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/reset-password',
        operationId: 'resetPassword',
        summary: 'Reset Password',
        description: 'Reset user password using the token from forgot password email',
        tags: ['Authentication', 'Password Reset']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['token', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(
                    property: 'token',
                    type: 'string',
                    description: 'Password reset token from the email',
                    example: 'abc123def456ghi789'
                ),
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
                    description: 'New password (min 8 characters with uppercase, lowercase, number, and special character)',
                    example: 'NewPassword123!'
                ),
                new OA\Property(
                    property: 'password_confirmation',
                    type: 'string',
                    format: 'password',
                    description: 'Password confirmation',
                    example: 'NewPassword123!'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Password reset successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Your password has been reset!'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'The given data was invalid.'
                ),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    example: ['token' => ['This password reset token is invalid.']]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        try {

            $status = $action->run($request);

            return Response::ok($status);
        } catch (Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }
}
