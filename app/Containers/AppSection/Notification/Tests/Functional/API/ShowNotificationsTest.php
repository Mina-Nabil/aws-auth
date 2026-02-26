<?php

namespace App\Containers\AppSection\Notification\Tests\Functional\API;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\Notification\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Notification\API\Controllers\NotificationController;
use App\Ship\Notifications\CriticalNotification;
use App\Ship\Notifications\InfoNotification;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NotificationController::class)]
final class ShowNotificationsTest extends ApiTestCase
{
    public function testCanListNotificationsAsAuthenticatedUser(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        // Create some test notifications
        $user->notify(new InfoNotification('Test Notification 1', 'Test notification 1'));
        $user->notify(new InfoNotification('Test Notification 2', 'Test notification 2'));

        $response = $this->getJson(action([NotificationController::class, 'index']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data', 2)
                ->has(
                    'data.0',
                    fn (AssertableJson $json): AssertableJson => $json
                    ->hasAll(['id', 'type', 'data', 'read_at', 'created_at'])
                    ->etc()
                )
                ->has('meta.pagination')
                ->etc(),
        );
    }

    public function testGivenUnauthenticatedUserPreventsOperation(): void
    {
        $response = $this->getJson(action([NotificationController::class, 'index']));

        $response->assertUnauthorized();
    }

    public function testListNotificationsReturnsEmptyDataWhenNoNotifications(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $response = $this->getJson(action([NotificationController::class, 'index']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json->has('data', 0)
                ->etc(),
        );
    }

    public function testListNotificationsOrdersByLatestFirst(): void
    {
        /** @var User $user */
        $user = User::factory()->user()->createOne();
        $this->actingAs($user);

        $firstNotification = new InfoNotification('First Notification', 'First notification');
        $secondNotification = new CriticalNotification('Second Notification', 'Second notification');

        // Create notifications with slight delay to ensure different timestamps
        $user->notify($firstNotification);
        sleep(1);
        $user->notify($secondNotification);

        $response = $this->getJson(action([NotificationController::class, 'index']));

        $response->assertOk();
        $response->assertJson(
            static fn (AssertableJson $json): AssertableJson => $json
                ->has('data', 2)
                ->where('data.0.type', 'critical')
                ->where('data.1.type', 'info')
                ->etc(),
        );
    }
}
