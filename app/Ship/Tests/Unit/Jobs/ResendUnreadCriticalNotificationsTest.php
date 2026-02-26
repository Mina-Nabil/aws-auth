<?php

namespace App\Ship\Tests\Unit\Jobs;

use App\Containers\AppSection\User\Models\User;
use App\Ship\Jobs\ResendUnreadCriticalNotifications;
use App\Ship\Notifications\CriticalNotification;
use App\Ship\Notifications\InfoNotification;
use App\Ship\Notifications\MailNotification;
use App\Ship\Tests\ShipTestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResendUnreadCriticalNotifications::class)]
final class ResendUnreadCriticalNotificationsTest extends ShipTestCase
{
    private ResendUnreadCriticalNotifications $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->job = new ResendUnreadCriticalNotifications();

        // Set default mail delay configuration
        Config::set('notifications.mail_delay', 3);

        // Set queue connection to sync for immediate processing in tests
        Config::set('queue.default', 'sync');
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ResendUnreadCriticalNotifications::class, $this->job);
    }

    public function testResendsCriticalNotificationsToActiveUsersWithinTimeRange(): void
    {
        // Create active user
        $activeUser = User::factory()->create(['is_disabled' => false]);

        // Create critical notification within the time range (25 hours ago)
        $criticalNotification = new CriticalNotification('Test Critical', 'Critical message');
        $activeUser->notify($criticalNotification);

        // Update the notification's created_at to be within range
        $notification = $activeUser->unreadNotifications()->first();
        $notification->update(['created_at' => now()->subHours(4)]);

        // Fake notifications after creating the test data
        Notification::fake();

        // Execute the job
        $this->job->handle();

        // Assert mail notification was sent
        Notification::assertSentTo(
            $activeUser,
            MailNotification::class,
            function ($notification) {
                return $notification->title === 'Test Critical'
                    && $notification->message === 'Critical message';
            }
        );
    }

    public function testDoesNotResendNotificationsToInactiveUsers(): void
    {
        // Create inactive user (disabled)
        $inactiveUser = User::factory()->create(['is_disabled' => true]);

        // Create critical notification
        $criticalNotification = new CriticalNotification('Test Critical', 'Critical message');
        $inactiveUser->notify($criticalNotification);

        // Update the notification's created_at to be within range
        $notification = $inactiveUser->unreadNotifications()->first();
        $notification->update(['created_at' => now()->subHours(4)]);

        // Fake notifications after creating the test data
        Notification::fake();

        // Execute the job
        $this->job->handle();

        // Assert no mail notification was sent
        Notification::assertNotSentTo($inactiveUser, MailNotification::class);
    }

    public function testDoesNotResendNotificationsOutsideTimeRange(): void
    {
        // Create active user
        $activeUser = User::factory()->create(['is_disabled' => false]);

        // Create critical notification outside time range (too old)
        $criticalNotification = new CriticalNotification('Test Critical', 'Critical message');
        $activeUser->notify($criticalNotification);

        // Update the notification's created_at to be outside range (5 hours ago)
        $notification = $activeUser->unreadNotifications()->first();
        $notification->update(['created_at' => now()->subHours(5)]);

        // Fake notifications after creating the test data
        Notification::fake();

        // Execute the job
        $this->job->handle();

        // Assert no mail notification was sent
        Notification::assertNotSentTo($activeUser, MailNotification::class);
    }

    public function testDoesNotResendNotificationsTooRecent(): void
    {
        // Create active user
        $activeUser = User::factory()->create(['is_disabled' => false]);

        // Create critical notification too recent (2 hours ago)
        $criticalNotification = new CriticalNotification('Test Critical', 'Critical message');
        $activeUser->notify($criticalNotification);

        // Update the notification's created_at to be too recent
        $notification = $activeUser->unreadNotifications()->first();
        $notification->update(['created_at' => now()->subHours(2)]);

        // Fake notifications after creating the test data
        Notification::fake();

        // Execute the job
        $this->job->handle();

        // Assert no mail notification was sent
        Notification::assertNotSentTo($activeUser, MailNotification::class);
    }

    public function testOnlyProcessesCriticalTypeNotifications(): void
    {

        // Create active user
        /** @var User $activeUser */
        $activeUser = User::factory()->create(['is_disabled' => false]);

        // Create a non-critical notification
        $notification = new InfoNotification('Info', 'Info message');
        $activeUser->notify($notification);
        sleep(1);

        // Fake notifications after creating the test data
        Notification::fake();

        // Update the notification's created_at to be within range
        $notification = $activeUser->unreadNotifications()->first();
        $notification->update(['created_at' => now()->subHours(4)]);

        // Execute the job
        $this->job->handle();

        // Assert no mail notification was sent for non-critical notification
        Notification::assertNotSentTo($activeUser, MailNotification::class);
    }

    public function testProcessesMultipleUsersWithCriticalNotifications(): void
    {
        // Create multiple active users
        $user1 = User::factory()->create(['is_disabled' => false]);
        $user2 = User::factory()->create(['is_disabled' => false]);
        $user3 = User::factory()->create(['is_disabled' => true]); // inactive

        // Create critical notifications for all users
        foreach ([$user1, $user2, $user3] as $user) {
            $criticalNotification = new CriticalNotification('Test Critical', 'Critical message');
            $user->notify($criticalNotification);

            // Update the notification's created_at to be within range
            $notification = $user->unreadNotifications()->first();
            $notification->update(['created_at' => now()->subHours(4)]);
        }

        // Fake notifications after creating the test data
        Notification::fake();

        // Execute the job
        $this->job->handle();

        // Assert mail notifications were sent only to active users
        Notification::assertSentTo($user1, MailNotification::class);
        Notification::assertSentTo($user2, MailNotification::class);
        Notification::assertNotSentTo($user3, MailNotification::class);
    }

    public function testProcessesMultipleCriticalNotificationsForSameUser(): void
    {
        // Create active user
        $activeUser = User::factory()->create(['is_disabled' => false]);

        // Create multiple critical notifications
        $criticalNotification1 = new CriticalNotification('Critical 1', 'First critical message');
        $criticalNotification2 = new CriticalNotification('Critical 2', 'Second critical message');

        $activeUser->notify($criticalNotification1);
        $activeUser->notify($criticalNotification2);

        // Update both notifications' created_at to be within range
        $activeUser->unreadNotifications()->each(function ($notification) {
            $notification->update(['created_at' => now()->subHours(4)]);
        });

        // Fake notifications after creating the test data
        Notification::fake();

        // Execute the job
        $this->job->handle();

        // Assert mail notifications were sent for both critical notifications
        Notification::assertSentToTimes($activeUser, MailNotification::class, 2);
    }

    public function testRespectsCustomMailDelayConfiguration(): void
    {
        // Set custom mail delay
        Config::set('notifications.mail_delay', 48);

        // Create active user
        $activeUser = User::factory()->create(['is_disabled' => false]);

        // Create critical notification within new time range (49 hours ago)
        $criticalNotification = new CriticalNotification('Test Critical', 'Critical message');
        $activeUser->notify($criticalNotification);

        // Update the notification's created_at to be within new range
        $notification = $activeUser->unreadNotifications()->first();
        $notification->update(['created_at' => now()->subHours(49)]);

        // Fake notifications after creating the test data
        Notification::fake();

        // Execute the job
        $this->job->handle();

        // Assert mail notification was sent
        Notification::assertSentTo($activeUser, MailNotification::class);
    }

    public function testHandlesUsersWithNoNotifications(): void
    {
        Notification::fake();

        // Create active user with no notifications
        $activeUser = User::factory()->create(['is_disabled' => false]);

        // Execute the job
        $this->job->handle();

        // Assert no mail notification was sent
        Notification::assertNothingSent();
    }

    public function testDoesNotProcessReadNotifications(): void
    {
        Notification::fake();

        // Create active user
        $activeUser = User::factory()->create(['is_disabled' => false]);

        // Create critical notification
        $criticalNotification = new CriticalNotification('Test Critical', 'Critical message');
        $activeUser->notify($criticalNotification);

        // Mark notification as read
        $notification = $activeUser->unreadNotifications()->first();
        if ($notification) {
            $notification->markAsRead();
            $notification->update(['created_at' => now()->subHours(4)]);
        }

        // Fake notifications after creating the test data
        Notification::fake();

        // Execute the job
        $this->job->handle();

        // Assert no mail notification was sent for read notification
        Notification::assertNotSentTo($activeUser, MailNotification::class);
    }
}
