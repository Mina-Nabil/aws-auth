<?php

namespace App\Containers\AppSection\Notification\API\Controllers;

use Illuminate\Http\JsonResponse;
use Apiato\Support\Facades\Response;
use App\Ship\Parents\Controllers\ApiController;
use App\Containers\AppSection\Notification\API\Transformers\NotificationTransformer;
use App\Containers\AppSection\Notification\API\Requests\ShowNotificationsRequest;
use App\Containers\AppSection\Notification\Actions\ShowNotificationsAction;
use App\Containers\AppSection\Notification\Actions\UpdateNotificationsReadAtAction;
use App\Containers\AppSection\Notification\API\Requests\UpdateNotificationsReadAtRequest;
use Illuminate\Auth\Access\AuthorizationException;
use App\Ship\Parents\Exceptions\DBCallException;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Notifications',
    description: 'Operations related to user notifications'
)]
final class NotificationController extends ApiController
{
    #[OA\Get(
        path: '/notifications',
        summary: 'List user notifications',
        description: 'Retrieve a paginated list of notifications for the authenticated user, ordered by latest first',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully retrieved notifications',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Notification')
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'pagination',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'integer', example: 50),
                                        new OA\Property(property: 'count', type: 'integer', example: 15),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'total_pages', type: 'integer', example: 4),
                                        new OA\Property(
                                            property: 'links',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'first', type: 'string', nullable: true),
                                                new OA\Property(property: 'last', type: 'string', nullable: true),
                                                new OA\Property(property: 'prev', type: 'string', nullable: true),
                                                new OA\Property(property: 'next', type: 'string', nullable: true),
                                            ]
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing authentication token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User not authorized to list notifications',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'You are not authorized to list notifications')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Internal server error')
                    ]
                )
            )
        ]
    )]
    public function index(ShowNotificationsRequest $request, ShowNotificationsAction $action): JsonResponse
    {
        try {
            $notifications = $action->run();
            return Response::create($notifications, NotificationTransformer::class)->ok();
        } catch (AuthorizationException $e) {
            return Response::forbidden("You are not authorized to list notifications");
        } catch (DBCallException $e) {
            report($e);
            return Response::serverError($e->getMessage());
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }

    #[OA\Post(
        path: '/notifications',
        summary: 'Mark notifications as read',
        description: 'Mark specified notifications as read by providing their IDs',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['notification_ids'],
                properties: [
                    new OA\Property(
                        property: 'notification_ids',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'uuid'),
                        description: 'Array of notification IDs to mark as read',
                        example: [
                            '550e8400-e29b-41d4-a716-446655440000',
                            '6ba7b810-9dad-11d1-80b4-00c04fd430c8'
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully marked notifications as read',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Request completed successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or missing authentication token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - Invalid request data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'notification_ids',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['The notification ids field is required.']
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Internal server error')
                    ]
                )
            )
        ]
    )]
    public function update(UpdateNotificationsReadAtRequest $request, UpdateNotificationsReadAtAction $action): JsonResponse
    {
        try {
            $action->run($request->notification_ids);
            return Response::ok();
        } catch (Exception $e) {
            report($e);
            return Response::serverError();
        }
    }
}
