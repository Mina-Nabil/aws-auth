<?php

namespace App\Containers\AppSection\User\Data\Seeders;

use App\Containers\AppSection\User\Data\Factories\UserFactory;
use App\Ship\Parents\Seeders\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 regular users
        UserFactory::new()
            ->count(10)
            ->user()
            ->create();

        // Create 2 admin users
        UserFactory::new()
            ->count(2)
            ->admin()
            ->create();

        // Create 1 super admin user
        UserFactory::new()
            ->count(1)
            ->superAdmin()
            ->create();
    }
}
