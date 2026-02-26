<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AddActionCommand extends Command
{
    protected $signature = 'safetyshield:add:action {name? : The action name} {--section= : The section name} {--container= : The container name}';

    protected $description = 'Add a new Action to an existing container';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Action Generator');
        $this->newLine();

        // Get action name
        $name = $this->argument('name') ?? $this->ask('Enter action name (e.g., CreateProduct, UpdateProduct)');

        if (empty($name)) {
            $this->error('Action name is required!');
            return self::FAILURE;
        }

        $actionName = Str::studly($name);
        if (!Str::endsWith($actionName, 'Action')) {
            $actionName .= 'Action';
        }

        // Get section and container
        $section = $this->option('section') ?? $this->ask('Enter section name', 'AppSection');
        $container = $this->option('container') ?? $this->ask('Enter container name');

        if (empty($container)) {
            $this->error('Container name is required!');
            return self::FAILURE;
        }

        $containerName = Str::studly($container);
        $containerPath = app_path("Containers/{$section}/{$containerName}");

        // Check if container exists
        if (!File::exists($containerPath)) {
            $this->error("Container '{$containerName}' does not exist in '{$section}' section!");
            $this->line("Create it first using: php artisan safetyshield:create:container");
            return self::FAILURE;
        }

        // Check if Actions directory exists
        $actionsPath = "{$containerPath}/Actions";
        if (!File::exists($actionsPath)) {
            File::makeDirectory($actionsPath, 0755, true);
        }

        $actionFilePath = "{$actionsPath}/{$actionName}.php";

        // Check if action already exists
        if (File::exists($actionFilePath)) {
            $this->error("Action '{$actionName}' already exists in '{$containerName}' container!");
            return self::FAILURE;
        }

        // Create the action file
        $this->createActionFile($actionFilePath, $section, $containerName, $actionName);

        $this->newLine();
        $this->info('✅ Action created successfully!');
        $this->info("📁 Location: {$actionFilePath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Add constructor dependencies (Tasks, Repositories, etc.)");
        $this->line("   2. Implement the run() method with your business logic");
        $this->line("   3. Add proper authorization using Gate::authorize()");
        $this->line("   4. Create unit test: php artisan apiato:make:test:unit {$actionName}Test --container={$containerName}");

        return self::SUCCESS;
    }

    private function createActionFile(string $filePath, string $section, string $container, string $actionName): void
    {
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Actions;

use App\\Ship\\Parents\\Actions\\Action as ParentAction;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Gate;
use Illuminate\\Support\\Facades\\Log;
use App\\Ship\\Parents\\Exceptions\\DBCallException;
use Exception;

final class {$actionName} extends ParentAction
{
    public function __construct(
        // TODO: Inject your dependencies here (Tasks, Repositories, etc.)
    ) {
    }

    /**
     * Execute the action.
     *
     * @param mixed \$data The input data
     * @return mixed The result
     * @throws DBCallException If there is a database error
     */
    public function run(mixed \$data): mixed
    {
        // TODO: Add authorization
        // Gate::authorize({$container}Gate::YOUR_GATE);

        Log::info('{$actionName} started', ['data' => \$data]);

        try {
            return DB::transaction(function () use (\$data) {
                // TODO: Implement your business logic here
                // Call tasks, manipulate data, etc.
                
                Log::info('{$actionName} completed');
                
                return \$data;
            });
        } catch (Exception \$e) {
            Log::error('{$actionName} failed', ['error' => \$e->getMessage()]);
            throw new DBCallException('Error in {$actionName}', 0, \$e);
        }
    }
}

PHP;

        File::put($filePath, $content);
        $this->line("  ✓ Created: Actions/{$actionName}.php");
    }
}
