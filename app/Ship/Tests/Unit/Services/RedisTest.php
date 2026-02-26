<?php

namespace App\Ship\Tests\Unit\Services;

use App\Ship\Tests\ShipTestCase;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class RedisTest extends ShipTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up test keys before each test
        $this->cleanupTestKeys();
    }

    protected function tearDown(): void
    {
        // Clean up test keys after each test
        $this->cleanupTestKeys();

        parent::tearDown();
    }

    public function testRedisConnectionIsWorking(): void
    {
        $response = Redis::connection()->ping();
        $this->assertEquals('PONG', (string) $response, 'Redis connection should respond to ping');
    }

    public function testRedisCanStoreAndRetrieveValues(): void
    {
        $key = 'test:redis:store';
        $value = 'test-value-' . time();

        Redis::set($key, $value);
        $retrieved = Redis::get($key);

        $this->assertEquals($value, $retrieved, 'Redis should store and retrieve values correctly');
    }

    public function testRedisCanDeleteValues(): void
    {
        $key = 'test:redis:delete';
        $value = 'test-value';

        Redis::set($key, $value);
        $this->assertNotNull(Redis::get($key), 'Value should exist before deletion');

        Redis::del($key);
        $this->assertNull(Redis::get($key), 'Value should be null after deletion');
    }

    public function testRedisCanSetAndGetWithExpiration(): void
    {
        $key = 'test:redis:expiration';
        $value = 'test-value-expiring';

        Redis::setex($key, 60, $value);
        $retrieved = Redis::get($key);

        $this->assertEquals($value, $retrieved, 'Redis should store value with expiration');

        $ttl = Redis::ttl($key);
        $this->assertGreaterThan(0, $ttl, 'TTL should be greater than 0');
        $this->assertLessThanOrEqual(60, $ttl, 'TTL should be less than or equal to 60 seconds');
    }

    public function testRedisCacheConnectionIsWorking(): void
    {
        $cacheConnection = Redis::connection('cache');
        $response = $cacheConnection->ping();
        $this->assertEquals('PONG', (string) $response, 'Redis cache connection should respond to ping');
    }

    protected function cleanupTestKeys(): void
    {
        try {
            $keys = Redis::keys('test:redis:*');
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }
}
