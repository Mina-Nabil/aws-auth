<?php

namespace App\Containers\AppSection\Authentication\Tests\Functional\API\PasswordReset;

use App\Containers\AppSection\Authentication\Tests\Functional\ApiTestCase;
use App\Containers\AppSection\Authentication\UI\API\Controllers\PasswordResetController;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PasswordResetController::class)]
final class ForgotPasswordTest extends ApiTestCase
{
    public function testForgotPassword(): void
    {
        $data = [
            'email' => 'admin@admin.com',
        ];

        $response = $this->postJson(action([PasswordResetController::class, 'forgotPassword']), $data);

        $response->assertJsonValidationErrors('email');
    }
}
