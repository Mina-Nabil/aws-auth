<?php

namespace App\Containers\AppSection\User\Tests\Functional\CLI;

use App\Containers\AppSection\User\Models\User;
use App\Containers\AppSection\User\Tests\Functional\CliTestCase;
use App\Containers\AppSection\User\CLI\CreateAdmin;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateAdmin::class)]
final class CreateAdminTest extends CliTestCase
{
    public function testCanCreateAdmin(): void
    {
        config(['appSection-authorization.require_pw_reset' => false]);

        $data = [
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'new@admin.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $this->artisan('create:admin')
            ->expectsQuestion('Enter the first name for this user', $data['first_name'])
            ->expectsQuestion('Enter the last name for this user', $data['last_name'])
            ->expectsQuestion('Enter the email address of this user', $data['email'])
            ->expectsQuestion('Enter the password for this user', $data['password'])
            ->expectsQuestion('Please confirm the password', $data['password_confirmation'])
            // ->expectsOutput('Admin ' . $data['email'] . ' successfully created')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);
        $this->assertTrue(Hash::check($data['password'], User::where('email', $data['email'])->first()->password));
    }

    public function testCanCreateAdminInRequirePwResetMode(): void
    {
        config(['appSection-authorization.require_pw_reset' => true]);

        $data = [
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'new2@admin.com',
        ];
        $this->artisan('create:admin')
            ->expectsQuestion('Enter the first name for this user', $data['first_name'])
            ->expectsQuestion('Enter the last name for this user', $data['last_name'])
            ->expectsQuestion('Enter the email address of this user', $data['email'])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);

        $user = User::where('email', $data['email'])->first();
        $this->assertTrue($user->password_reset_required);
    }

    public function testErrorsOnNotMatchingPassword(): void
    {
        config(['appSection-authorization.require_pw_reset' => false]);

        $data = [
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'new@admin.com',
            'password' => 'password',
            'password_confirmation' => 'not_matching_password',
        ];

        $this->artisan('create:admin')
            ->expectsQuestion('Enter the first name for this user', $data['first_name'])
            ->expectsQuestion('Enter the last name for this user', $data['last_name'])
            ->expectsQuestion('Enter the email address of this user', $data['email'])
            ->expectsQuestion('Enter the password for this user', $data['password'])
            ->expectsQuestion('Please confirm the password', $data['password_confirmation'])
            ->expectsOutput('Passwords does not match - exiting!')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);
    }
}
