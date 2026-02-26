<?php

namespace App\Ship\Listeners;

use App\Ship\Parents\Listeners\Listener;
use Exception;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class HealthCheckListener extends Listener
{
    public function handle(DiagnosingHealth $event): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new Exception('Database connection check failed');
        }

        # Removed redis health check while redis is not in use
        //        try {
        //            Redis::connection()->ping();
        //        } catch (\Exception $e) {
        //            throw new Exception('Redis connection check failed');
        //        }
    }
}
