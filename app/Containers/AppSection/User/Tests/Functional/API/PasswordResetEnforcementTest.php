<?php

namespace App\Containers\AppSection\User\Tests\Functional\API;

use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\Functional\ApiTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCRUDController::class)]
final class PasswordResetEnforcementTest extends ApiTestCase
{
    public function testUserWithPasswordResetRequiredCannotAccessRegularEndpoints(): void
    {
        $user = User::factory()->createOne([
            'password_reset_required' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(action([UserCRUDController::class, 'users']));

        $response->assertForbidden();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json
                ->where('message', 'Password reset required. Please update your password before accessing other resources.')
                ->etc(),
        );
    }

    public function testUserWithPasswordResetRequiredCanAccessProfileEndpoint(): void
    {
        $user = User::factory()->createOne([
            'password_reset_required' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(action([UserCRUDController::class, 'getLoggedInUserProfile']));

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('email', $user->email)
                    ->etc(),
            )->etc(),
        );
    }

    public function testUserWithoutPasswordResetCanAccessRegularEndpoints(): void
    {
        $user = User::factory()->admin()->createOne([
            'password_reset_required' => false,
        ]);

        $this->actingAs($user);

        $response = $this->get(action([UserCRUDController::class, 'users']));

        $response->assertOk();
    }

    public function testPasswordResetFlagIsClearedAfterSuccessfulPasswordUpdate(): void
    {
        $user = User::factory()->admin()->createOne([
            'password' => Hash::make('OldPassword123!'),
            'password_reset_required' => true,
        ]);

        $this->actingAs($user);

        $this->patch(
            action([UserCRUDController::class, 'updateUserPassword'], ['user_id' => $user->id]),
            [
                'current_password' => 'OldPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ]
        );

        $user->refresh();
        $this->assertFalse($user->password_reset_required);

        $response = $this->get(action([UserCRUDController::class, 'users']));
        $response->assertOk();
    }
}
