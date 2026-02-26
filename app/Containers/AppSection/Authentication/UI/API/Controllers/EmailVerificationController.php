<?php

namespace App\Containers\AppSection\Authentication\UI\API\Controllers;

use Apiato\Support\Facades\Response;
use App\Containers\AppSection\Authentication\UI\API\Requests\EmailVerification\SendRequest;
use App\Containers\AppSection\Authentication\UI\API\Requests\EmailVerification\VerifyRequest;
use App\Containers\AppSection\Authentication\Actions\EmailVerification\SendAction;
use App\Containers\AppSection\Authentication\Actions\EmailVerification\VerifyAction;
use App\Ship\Parents\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Exception;

final class EmailVerificationController extends ApiController
{
    #[OA\Post(
        path: '/email/verification-notification',
        operationId: 'sendEmailVerification',
        summary: 'Send Email Verification',
        description: 'Send email verification notification to the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Authentication', 'Email Verification']
    )]
    #[OA\Response(
        response: 202,
        description: 'Email verification notification sent successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Email verification notification sent'
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 429, description: 'Too many requests')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function sendVerificationNotification(SendRequest $request, SendAction $action): JsonResponse
    {
        try {
            $action->run($request->user());
            return Response::accepted();
        } catch (Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/email/verify/{id}/{hash}',
        operationId: 'verifyEmail',
        summary: 'Verify Email',
        description: 'Verify user email address using verification link from the email',
        security: [['bearerAuth' => []]],
        tags: ['Authentication', 'Email Verification']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'User ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '01234567-89ab-cdef-0123-456789abcdef')
    )]
    #[OA\Parameter(
        name: 'hash',
        description: 'Verification hash',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'abc123def456')
    )]
    #[OA\Parameter(
        name: 'expires',
        description: 'Expiration timestamp',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1640995200)
    )]
    #[OA\Parameter(
        name: 'signature',
        description: 'URL signature',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'signature_hash')
    )]
    #[OA\Response(
        response: 200,
        description: 'Email verified successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Email verified successfully'
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Invalid verification link')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Email already verified')]
    #[OA\Response(response: 500, description: 'Internal server error')]
    public function verifyEmail(VerifyRequest $request, VerifyAction $action): JsonResponse
    {
        try {
            $action->run($request->user());
            return Response::ok();
        } catch (Exception $e) {
            report($e);
            return Response::serverError($e->getMessage());
        }
    }
}
