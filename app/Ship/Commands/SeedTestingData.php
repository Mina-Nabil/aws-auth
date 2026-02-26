<?php

namespace App\Ship\Commands;

use Apiato\Core\Console\Command as ParentCommand;
use App\Ship\Seeders\TestingDataSeeder;

final class SeedTestingData extends ParentCommand
{
    protected $signature = 'apiato:seed-test {--force}';
    protected $description = 'Seed testing data.';

    public function handle(): void
    {
        $options = [];
        if ($this->option('force')) {
            $options['--force'] = true;
        }
        $this->call('db:seed', array_merge($options, [
            '--class' => TestingDataSeeder::class,
        ]));

        $this->info('Testing Data Seeded Successfully.');
    }
}
