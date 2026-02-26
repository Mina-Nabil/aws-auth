<?php

namespace App\Ship\Tests\Unit\Parents\Tests;

use App\Ship\Parents\Tests\DBRefreshTestCase;
use App\Ship\Tests\ShipTestCase;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DBRefreshTestCase::class)]
final class DBRefreshTestCaseTest extends ShipTestCase
{
    public function testCanCreateApplication(): void
    {
        $testCase = new DBRefreshFakeTestCase('test');
        $application = $testCase->createApplication();

        $this->assertInstanceOf(Application::class, $application);
    }
}

class DBRefreshFakeTestCase extends DBRefreshTestCase
{
}
