<?php

namespace App\Containers\AppSection\Notification\Tests\Unit\Actions;

use App\Containers\AppSection\Notification\Actions\ShowNotificationsAction;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\Notification\Tests\UnitTestCase;
use App\Ship\Notifications\InfoNotification;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ShowNotificationsAction::class)]
final class ShowNotificationsActionTest extends UnitTestCase
{
    public function testCanListNotificationsForAuthenticatedUser(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        // Create test notifications
        $user->notify(new InfoNotification('Test Notification 1', 'Test notification 1'));
        sleep(1);
        $user->notify(new InfoNotification('Test Notification 2', 'Test notification 2'));

        $action = app(ShowNotificationsAction::class);
        $result = $action->run();

        $this->assertCount(2, $result);
        $this->assertEquals('Test notification 2', $result->first()->data['message']);
        $this->assertEquals('Test notification 1', $result->last()->data['message']);
    }

    public function testListNotificationsReturnsEmptyCollectionWhenNoNotifications(): void
    {
        /** @var User $user */
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        $action = app(ShowNotificationsAction::class);
        $result = $action->run();

        $this->assertCount(0, $result);
    }

    public function testListNotificationsOrdersByLatestFirst(): void
    {
        /** @var User $user */
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        // Create notifications with different timestamps
        $user->notify(new InfoNotification('First Notification', 'First notification'));
        sleep(1);
        $user->notify(new InfoNotification('Second Notification', 'Second notification'));

        $action = app(ShowNotificationsAction::class);
        $result = $action->run();

        $this->assertCount(2, $result);
        $this->assertEquals('Second notification', $result->first()->data['message']);
        $this->assertEquals('First notification', $result->last()->data['message']);
    }

    public function testListNotificationsReturnsPaginatedResult(): void
    {
        /** @var User $user */
        $user = User::factory()->admin()->createOne();
        $this->actingAs($user);

        // Create multiple notifications
        for ($i = 1; $i <= 20; $i++) {
            $user->notify(new InfoNotification("Notification $i Title", "Notification $i"));
        }

        $action = app(ShowNotificationsAction::class);
        $result = $action->run();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage()); // Default pagination
        $this->assertEquals(20, $result->total());
    }
}
