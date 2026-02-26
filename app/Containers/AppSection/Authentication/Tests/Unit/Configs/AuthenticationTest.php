<?php

namespace App\Containers\AppSection\Authentication\Tests\Unit\Configs;

use App\Containers\AppSection\Authentication\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class AuthenticationTest extends UnitTestCase
{
    public function testConfigHasCorrectValues(): void
    {
        $config = config('appSection-authentication');
        $expected = [
            'clients' => [
                'web' => [
                    'id' => env('CLIENT_WEB_ID'),
                    'secret' => env('CLIENT_WEB_SECRET'),
                ],
            ],
            'tokens-expire-in' => env('API_TOKEN_EXPIRES', 1440),
            'refresh-tokens-expire-in' => env('API_REFRESH_TOKEN_EXPIRES', 43200),
            'user_registration_enabled' => env('AUTH_REG_ENABLED', false),
            'api-url' => env('API_URL', 'http://localhost'),
            'app-url' => env('APP_URL', 'http://localhost'),
            'github-url' => env('GITHUB_REPO_URL', 'https://github.com/Safety-Shield/system-template'),
        ];

        $this->assertEquals($expected, $config);
    }
}
