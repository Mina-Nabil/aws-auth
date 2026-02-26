<?php

namespace App\Containers\AppSection\Authentication\Tests\Functional\WEB;

use App\Containers\AppSection\Authentication\Tests\Functional\WebTestCase;
use App\Containers\AppSection\Authentication\UI\WEB\Controllers\HomePageController;
use App\Containers\AppSection\Authentication\UI\WEB\Controllers\LoginController;
use App\Containers\AppSection\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Cookie;

#[CoversClass(LoginController::class)]
final class LoginTest extends WebTestCase
{
    public function testCanLoginWithCredentials(): void
    {
        $user = User::factory()->createOne([
            'email' => 'gandalf@the.grey',
            'password' => 'youShallNotPass',
        ]);

        $response = $this
            ->post(action(LoginController::class), [
                'email' => $user->email,
                'password' => 'youShallNotPass',
                'remember' => true,
            ]);

        $response->assertRedirect(action(HomePageController::class));
        $this->assertAuthenticatedAs($user, 'web');
    }
}
