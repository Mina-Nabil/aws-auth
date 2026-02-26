<?php

namespace App\Ship\Tests\Unit\Apps;

use App\Ship\Apps\AppFactory;
use App\Ship\Apps\Api;
use App\Ship\Tests\ShipTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AppFactory::class)]
final class AppFactoryTest extends ShipTestCase
{
    public function testCanReturnCurrentApp(): void
    {
        request()->headers->set('App-Identifier', 'api');

        $result = AppFactory::current();

        $this->assertInstanceOf(Api::class, $result);
    }
}
