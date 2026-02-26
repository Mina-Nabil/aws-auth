<?php

namespace App\Containers\AppSection\Authorization\CLI;

use App\Containers\AppSection\Authorization\Actions\SyncContainersPermissionsAction;
use App\Ship\Parents\Commands\Command as ConsoleCommand;

class SyncPermissionsCommand extends ConsoleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync {--dry-run} {--prune}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions from each container\'s permissions.json file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private readonly SyncContainersPermissionsAction $syncContainersPermissionsAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Refreshing permissions'); // green color
        $dry = $this->option('dry-run');
        $prune = $this->option('prune');
        $this->syncContainersPermissionsAction->transactionalRun($dry, $prune);
        $this->info('Permissions synced'); // green color

    }


}
