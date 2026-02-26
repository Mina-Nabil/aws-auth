<?php

namespace App\Ship\Commands;

use Apiato\Core\Console\Command as ParentCommand;
use App\Ship\Seeders\InitialDeploymentDataSeeder;

final class SeedInitialDeploymentData extends ParentCommand
{
    protected $signature = 'apiato:seed-deploy {--force}';
    protected $description = 'Seed data for the initial deployment.';

    public function handle(): void
    {
        $options = [];
        if ($this->option('force')) {
            $options['--force'] = true;
        }
        $this->call('db:seed', array_merge($options, [
            '--class' => InitialDeploymentDataSeeder::class,
        ]));

        $this->info('Deployment Data Seeded Successfully.');
    }
}
