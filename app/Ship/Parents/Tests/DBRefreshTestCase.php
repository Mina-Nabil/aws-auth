<?php

namespace App\Ship\Parents\Tests;

use Apiato\Core\Testing\TestCase as AbstractTestCase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Support\Facades\Artisan;

/**
 * Use this class for testcases that requires a fresh db migration
 * before and after execution
 * Use this class as a last resort, prefer to use ContainerTestCase instead. It takes a lot of time to run the tests.
 */
abstract class DBRefreshTestCase extends AbstractTestCase
{
    use InteractsWithConsole;


    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->artisan('apiato:seed-deploy');
        $this->artisan('permissions:sync');
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDown();
        Artisan::call('migrate:fresh');
        Artisan::call('apiato:seed-deploy');
        Artisan::call('permissions:sync');
    }
}
