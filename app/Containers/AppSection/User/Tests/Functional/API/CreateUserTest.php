<?php

namespace App\Containers\AppSection\User\Tests\Functional\API;

use App\Containers\AppSection\Authentication\Data\Factories\ClientFactory;
use App\Containers\AppSection\Authentication\UI\API\Controllers\WebClient\IssueTokenController;
use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\User\API\Controllers\UserCRUDController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserCRUDController::class)]
final class CreateUserTest extends ApiTestCase
{
    public function testCanCreateUserInRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => true]);

        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('email', $data['email'])
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->etc(),
            )->etc(),
        );

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->password_reset_required);
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->email_verified_at);
    }

    public function testCanCreateUserInNotRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => false]);

        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $password = 'SecurePass123!';
        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->where('type', 'User')
                    ->where('email', $data['email'])
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->etc(),
            )->etc(),
        );

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->password_reset_required);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function testCanLoginAfterUserCreationInNotRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => false]);
        ClientFactory::webClient();

        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $password = 'SecurePass123!';
        $data = [
            'first_name' => 'Login',
            'last_name' => 'Test',
            'email' => 'logintest@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'user',
        ];

        $createResponse = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);
        $createResponse->assertCreated();

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->password_reset_required);

        // User should be able to login with the provided password
        $loginData = [
            'email' => $data['email'],
            'password' => $password,
        ];

        $loginResponse = $this->postJson(action(IssueTokenController::class), $loginData);
        $loginResponse->assertOk();
        $loginResponse->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->has(
                'data',
                fn (AssertableJson $json): AssertableJson => $json
                    ->hasAll(['access_token', 'refresh_token', 'token_type', 'expires_in'])
                    ->where('token_type', 'Bearer')
                    ->etc(),
            )->etc(),
        );
    }

    public function testCantLoginAfterUserCreationInRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => true]);
        ClientFactory::webClient();

        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'Login',
            'last_name' => 'Test',
            'email' => 'logintest@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'user',
        ];

        $createResponse = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);
        $createResponse->assertCreated();

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->password_reset_required);

        $loginData = [
            'email' => $data['email'],
            'password' => 'SecurePass123!',
        ];

        $loginResponse = $this->postJson(action(IssueTokenController::class), $loginData);
        $loginResponse->assertUnauthorized();
    }

    public function testValidationFailsWhenFirstNameIsMissing(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('first_name');
    }

    public function testValidationFailsWhenFirstNameIsTooShort(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'A',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('first_name');
    }

    public function testValidationFailsWhenFirstNameIsTooLong(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => str_repeat('A', 51),
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('first_name');
    }

    public function testValidationFailsWhenLastNameIsMissing(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('last_name');
    }

    public function testValidationFailsWhenLastNameIsTooShort(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'D',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('last_name');
    }

    public function testValidationFailsWhenLastNameIsTooLong(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => str_repeat('A', 51),
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('last_name');
    }

    public function testValidationFailsWhenEmailIsMissing(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function testValidationFailsWhenEmailIsInvalid(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function testValidationFailsWhenEmailAlreadyExists(): void
    {
        $existingUser = User::factory()->createOne(['email' => 'existing@example.com']);
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function testValidationFailsWhenRoleIsMissing(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('role');
    }

    public function testValidationFailsWhenRoleIsInvalid(): void
    {
        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'role' => 'invalid-role',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('role');
    }

    public function testValidationFailsWhenMissingPasswordInNotRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => false]);

        $admin = User::factory()->superAdmin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
    }

    public function testUnauthorizedAccessPreventsUserCreation(): void
    {
        // Test without authentication
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertUnauthorized();
    }

    public function testRegularUserCannotCreateUser(): void
    {
        $regularUser = User::factory()->user()->createOne();
        $this->actingAs($regularUser);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertForbidden();
    }

    public function testAdminCanCreateUserInRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => true]);

        $admin = User::factory()->admin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertCreated();
    }

    public function testAdminCanCreateUserInNotRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => false]);

        $admin = User::factory()->admin()->createOne();
        $this->actingAs($admin);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'user',
        ];

        $response = $this->postJson(action([UserCRUDController::class, 'createUser']), $data);

        $response->assertCreated();
    }
}
