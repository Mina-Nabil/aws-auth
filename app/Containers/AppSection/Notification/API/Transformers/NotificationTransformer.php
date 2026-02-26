<?php

namespace App\Containers\AppSection\Notification\API\Transformers;

use App\Ship\Parents\Transformers\Transformer as ParentTransformer;
use Illuminate\Notifications\DatabaseNotification;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Notification',
    title: 'Notification',
    description: 'Notification object representing a user notification',
    type: 'object',
    required: ['id', 'type', 'data', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(
            property: 'id',
            type: 'string',
            format: 'uuid',
            description: 'Unique identifier for the notification',
            example: '550e8400-e29b-41d4-a716-446655440000'
        ),
        new OA\Property(
            property: 'type',
            type: 'string',
            description: 'Notification type',
            example: 'info - critical'
        ),
        new OA\Property(
            property: 'data',
            type: 'object',
            description: 'JSON data containing the notification payload',
            example: [
                'title' => 'Welcome!',
                'message' => 'Thank you for joining our platform.',
                'action_url' => 'https://example.com/dashboard'
            ]
        ),
        new OA\Property(
            property: 'read_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            description: 'Timestamp when the notification was marked as read',
            example: '2023-10-15T14:30:00.000000Z'
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            description: 'Timestamp when the notification was created',
            example: '2023-10-15T10:15:00.000000Z'
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            description: 'Timestamp when the notification was last updated',
            example: '2023-10-15T14:30:00.000000Z'
        )
    ]
)]
class NotificationTransformer extends ParentTransformer
{
    public function transform(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => json_encode($notification->data, true),
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
            'updated_at' => $notification->updated_at,
        ];
    }
}
