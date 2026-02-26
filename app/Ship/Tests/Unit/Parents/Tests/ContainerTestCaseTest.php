<?php

namespace App\Ship\Tests\Unit\Parents\Tests;

use App\Ship\Parents\Tests\ContainerTestCase;
use App\Ship\Tests\ShipTestCase;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ContainerTestCase::class)]
final class ContainerTestCaseTest extends ShipTestCase
{
    public function testCanCreateApplication(): void
    {
        $testCase = new ContainerFakeTestCase('test');
        $application = $testCase->createApplication();

        $this->assertInstanceOf(Application::class, $application);
    }
}

class ContainerFakeTestCase extends ContainerTestCase
{
}
