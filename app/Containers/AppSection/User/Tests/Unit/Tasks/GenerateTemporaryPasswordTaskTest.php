<?php

namespace App\Containers\AppSection\User\Tests\Unit\Tasks;

use App\Containers\AppSection\User\Tasks\GenerateTemporaryPasswordTask;
use App\Containers\AppSection\User\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GenerateTemporaryPasswordTask::class)]
final class GenerateTemporaryPasswordTaskTest extends UnitTestCase
{
    public function testGeneratesSecureTemporaryPassword(): void
    {
        $password = app(GenerateTemporaryPasswordTask::class)->run();

        $this->assertGreaterThanOrEqual(12, strlen($password));
        $this->assertMatchesRegularExpression('/[a-zA-Z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        $this->assertMatchesRegularExpression('/[^a-zA-Z0-9]/', $password);
    }
}
