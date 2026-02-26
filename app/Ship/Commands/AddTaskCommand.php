<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AddTaskCommand extends Command
{
    protected $signature = 'safetyshield:add:task {name? : The task name} {--section= : The section name} {--container= : The container name}';

    protected $description = 'Add a new Task to an existing container';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Task Generator');
        $this->newLine();

        // Get task name
        $name = $this->argument('name') ?? $this->ask('Enter task name (e.g., FindProductById, UpdateProductRecord)');

        if (empty($name)) {
            $this->error('Task name is required!');
            return self::FAILURE;
        }

        $taskName = Str::studly($name);
        if (!Str::endsWith($taskName, 'Task')) {
            $taskName .= 'Task';
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

        // Check if Tasks directory exists
        $tasksPath = "{$containerPath}/Tasks";
        if (!File::exists($tasksPath)) {
            File::makeDirectory($tasksPath, 0755, true);
        }

        $taskFilePath = "{$tasksPath}/{$taskName}.php";

        // Check if task already exists
        if (File::exists($taskFilePath)) {
            $this->error("Task '{$taskName}' already exists in '{$containerName}' container!");
            return self::FAILURE;
        }

        // Create the task file
        $this->createTaskFile($taskFilePath, $section, $containerName, $taskName);

        $this->newLine();
        $this->info('✅ Task created successfully!');
        $this->info("📁 Location: {$taskFilePath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Add constructor dependencies (Repository, Services, etc.)");
        $this->line("   2. Implement the run() method with your task logic");
        $this->line("   3. Keep tasks focused on single responsibility");
        $this->line("   4. Create unit test: php artisan apiato:make:test:unit {$taskName}Test --container={$containerName}");

        return self::SUCCESS;
    }

    private function createTaskFile(string $filePath, string $section, string $container, string $taskName): void
    {
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Tasks;

use App\\Containers\\{$section}\\{$container}\\Data\\Repositories\\{$container}Repository;
use App\\Ship\\Parents\\Tasks\\Task as ParentTask;
use Illuminate\\Support\\Facades\\Log;

final class {$taskName} extends ParentTask
{
    public function __construct(
        private readonly {$container}Repository \$repository,
        // TODO: Inject additional dependencies here
    ) {
    }

    /**
     * Execute the task.
     *
     * @param mixed \$params The input parameters
     * @return mixed The result
     */
    public function run(mixed \$params): mixed
    {
        Log::info('{$taskName} started', ['params' => \$params]);

        // TODO: Implement your task logic here
        // Tasks should be simple, single-responsibility operations
        
        return null;
    }
}

PHP;

        File::put($filePath, $content);
        $this->line("  ✓ Created: Tasks/{$taskName}.php");
    }
}
