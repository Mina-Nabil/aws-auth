<?php

namespace App\Ship\Seeders;

use App\Containers\AppSection\Authorization\Data\Seeders\AuthorizationSeeder_1;
use App\Containers\AppSection\Authorization\Data\Seeders\SuperAdminSeeder_2;
use App\Ship\Parents\Seeders\Seeder;

final class InitialDeploymentDataSeeder extends Seeder
{
    /**
     * Note: This seeder is not loaded automatically by Apiato
     * This is a special seeder that can be called by "apiato:seed-deploy" command
     * It is useful for seeding data for initial deployment.
     */
    public function run(): void
    {
        $this->call(AuthorizationSeeder_1::class);
        $this->call(SuperAdminSeeder_2::class);
    }
}
