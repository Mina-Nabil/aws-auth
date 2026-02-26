<?php

namespace App\Containers\AppSection\Notification\Tests\Functional\API;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\Notification\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Notification\API\Controllers\NotificationController;
use App\Ship\Notifications\InfoNotification;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NotificationController::class)]
final class UpdateNotificationsReadAtTest extends ApiTestCase
{
    public function testCanMarkNotificationsAsReadAsAuthenticatedUser(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        // Create test notifications
        $user->notify(new InfoNotification('Test Notification 1', 'Test notification 1'));
        $user->notify(new InfoNotification('Test Notification 2', 'Test notification 2'));

        $notifications = $user->unreadNotifications()->get();
        $notificationIds = $notifications->pluck('id')->toArray();

        $response = $this->postJson(
            action([NotificationController::class, 'update']),
            ['notification_ids' => $notificationIds]
        );

        $response->assertOk();

        // Verify notifications are marked as read
        $user->refresh();
        $unreadCount = $user->unreadNotifications()->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function testGivenUnauthenticatedUserPreventsOperation(): void
    {
        $response = $this->postJson(
            action([NotificationController::class, 'update']),
            ['notification_ids' => ['some-uuid']]
        );

        $response->assertUnauthorized();
    }

    public function testGivenMissingNotificationIdsPreventsOperation(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $response = $this->postJson(
            action([NotificationController::class, 'update']),
            []
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['notification_ids']);
    }

    public function testGivenEmptyNotificationIdsArrayPreventsOperation(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $response = $this->postJson(
            action([NotificationController::class, 'update']),
            ['notification_ids' => []]
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['notification_ids']);
    }

    public function testGivenInvalidNotificationIdsFormatPreventsOperation(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $response = $this->postJson(
            action([NotificationController::class, 'update']),
            ['notification_ids' => ['invalid-id', 'another-invalid-id']]
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['notification_ids.0', 'notification_ids.1']);
    }

    public function testCanMarkPartialNotificationsAsRead(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        // Create multiple test notifications
        $user->notify(new InfoNotification('Test Notification 1', 'Test notification 1'));
        $user->notify(new InfoNotification('Test Notification 2', 'Test notification 2'));
        $user->notify(new InfoNotification('Test Notification 3', 'Test notification 3'));

        $notifications = $user->unreadNotifications()->get();
        $partialNotificationIds = $notifications->take(2)->pluck('id')->toArray();

        $response = $this->postJson(
            action([NotificationController::class, 'update']),
            ['notification_ids' => $partialNotificationIds]
        );

        $response->assertOk();

        // Verify only specified notifications are marked as read
        $user->refresh();
        $unreadCount = $user->unreadNotifications()->count();
        $this->assertEquals(1, $unreadCount);
    }
}
