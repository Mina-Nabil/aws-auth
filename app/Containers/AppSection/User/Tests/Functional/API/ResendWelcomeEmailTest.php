<?php

namespace App\Containers\AppSection\User\Tests\Functional\API;

use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Notifications\WelcomeNotification;
use App\Containers\AppSection\User\Tests\Functional\ApiTestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCRUDController::class)]
final class ResendWelcomeEmailTest extends ApiTestCase
{
    public function testAdminCanResendWelcomeEmail(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->createOne();
        $user = User::factory()->createOne([
            'password_reset_required' => false,
        ]);

        $this->actingAs($admin);

        $response = $this->post(action(
            [UserCRUDController::class, 'resendWelcomeEmail'],
            ['user_id' => $user->id],
        ));

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('id', $user->id)
                    ->where('email', $user->email)
                    ->etc(),
            )->etc(),
        );

        $user->refresh();
        $this->assertTrue($user->password_reset_required);

        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function testRegularUserCannotResendWelcomeEmail(): void
    {
        $user = User::factory()->user()->createOne();
        $targetUser = User::factory()->createOne();

        $this->actingAs($user);

        $response = $this->post(action(
            [UserCRUDController::class, 'resendWelcomeEmail'],
            ['user_id' => $targetUser->id],
        ));

        $response->assertForbidden();
    }

    public function testResendWelcomeEmailForNonExistentUser(): void
    {
        $admin = User::factory()->admin()->createOne();

        $this->actingAs($admin);

        $response = $this->post(action(
            [UserCRUDController::class, 'resendWelcomeEmail'],
            ['user_id' => 'non-existent-id'],
        ));

        $response->assertNotFound();
    }
}
