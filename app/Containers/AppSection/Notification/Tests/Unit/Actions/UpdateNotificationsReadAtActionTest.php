<?php

namespace App\Containers\AppSection\Notification\Tests\Unit\Actions;

use App\Containers\AppSection\Notification\Actions\UpdateNotificationsReadAtAction;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\Notification\Tests\UnitTestCase;
use App\Ship\Notifications\InfoNotification;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateNotificationsReadAtAction::class)]
final class UpdateNotificationsReadAtActionTest extends UnitTestCase
{
    public function testCanMarkNotificationsAsRead(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        // Create test notifications
        $user->notify(new InfoNotification('Test Notification 1', 'Test notification 1'));
        $user->notify(new InfoNotification('Test Notification 2', 'Test notification 2'));

        $notifications = $user->unreadNotifications()->get();
        $notificationIds = $notifications->pluck('id')->toArray();

        $action = app(UpdateNotificationsReadAtAction::class);
        $result = $action->run($notificationIds);

        $this->assertTrue($result);

        // Verify notifications are marked as read
        $user->refresh();
        $unreadCount = $user->unreadNotifications()->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function testCanMarkPartialNotificationsAsRead(): void
    {
        /** @var User $user */
        $user = User::factory()->superAdmin()->createOne();
        $this->actingAs($user);

        // Create test notifications
        $user->notify(new InfoNotification('Test Notification 1', 'Test notification 1'));
        $user->notify(new InfoNotification('Test Notification 2', 'Test notification 2'));
        $user->notify(new InfoNotification('Test Notification 3', 'Test notification 3'));

        $notifications = $user->unreadNotifications()->get();
        $partialNotificationIds = $notifications->take(2)->pluck('id')->toArray();

        $action = app(UpdateNotificationsReadAtAction::class);
        $result = $action->run($partialNotificationIds);

        $this->assertTrue($result);

        // Verify only specified notifications are marked as read
        $user->refresh();
        $unreadCount = $user->unreadNotifications()->count();
        $this->assertEquals(1, $unreadCount);
    }

    public function testReadNotificationsWithEmptyArrayReturnsTrue(): void
    {
        /** @var User $user */
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        $action = app(UpdateNotificationsReadAtAction::class);
        $result = $action->run([]);

        $this->assertTrue($result);
    }

    public function testReadNotificationsWithNonExistentIdsReturnsTrue(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $nonExistentIds = ['00000000-0000-0000-0000-000000000000', '11111111-1111-1111-1111-111111111111'];

        $action = app(UpdateNotificationsReadAtAction::class);
        $result = $action->run($nonExistentIds);

        $this->assertTrue($result);
    }

    public function testReadNotificationsLogsInfoAndDebugMessages(): void
    {
        /** @var User $user */
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        $notificationIds = ['test-id-1', 'test-id-2'];

        Log::shouldReceive('info')
            ->once()
            ->with('Reading notifications');

        Log::shouldReceive('debug')
            ->once()
            ->with('Notification IDs: test-id-1, test-id-2');

        $action = app(UpdateNotificationsReadAtAction::class);
        $action->run($notificationIds);
    }

    public function testReadNotificationsOnlyMarksUnreadNotifications(): void
    {
        /** @var User $user */
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        // Create notifications
        $user->notify(new InfoNotification('Test Notification 1', 'Test notification 1'));
        $user->notify(new InfoNotification('Test Notification 2', 'Test notification 2'));

        $notifications = $user->unreadNotifications()->get();
        /** @var Notification $firstNotification */
        $firstNotification = $notifications->first();

        // Mark first notification as read manually
        $firstNotification->markAsRead();

        // Try to mark both notifications as read via action
        $allNotificationIds = $notifications->pluck('id')->toArray();

        $action = app(UpdateNotificationsReadAtAction::class);
        $result = $action->run($allNotificationIds);

        $this->assertTrue($result);

        // Verify all notifications are now read
        $user->refresh();
        $unreadCount = $user->unreadNotifications()->count();
        $this->assertEquals(0, $unreadCount);
    }
}
