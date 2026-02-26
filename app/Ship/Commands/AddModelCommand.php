<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AddModelCommand extends Command
{
    protected $signature = 'safetyshield:add:model {name? : The model name} {--section= : The section name} {--container= : The container name}';

    protected $description = 'Add a new Model to an existing container';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Model Generator');
        $this->newLine();

        // Get model name
        $name = $this->argument('name') ?? $this->ask('Enter model name (e.g., Product, OrderItem)');

        if (empty($name)) {
            $this->error('Model name is required!');
            return self::FAILURE;
        }

        $modelName = Str::studly($name);

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
            return self::FAILURE;
        }

        $modelsPath = "{$containerPath}/Models";
        if (!File::exists($modelsPath)) {
            File::makeDirectory($modelsPath, 0755, true);
        }

        $modelFilePath = "{$modelsPath}/{$modelName}.php";

        // Check if model already exists
        if (File::exists($modelFilePath)) {
            $this->error("Model '{$modelName}' already exists!");
            return self::FAILURE;
        }

        $this->createModelFile($modelFilePath, $section, $containerName, $modelName);

        $this->newLine();
        $this->info('✅ Model created successfully!');
        $this->info("📁 Location: {$modelFilePath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Define fillable fields and casts");
        $this->line("   2. Add relationships and scopes");
        $this->line("   3. Create migration: php artisan safetyshield:add:migration create_{$this->getTableName($modelName)}_table --container={$containerName}");
        $this->line("   4. Create factory: php artisan apiato:make:factory {$modelName}Factory --container={$containerName}");

        return self::SUCCESS;
    }

    private function createModelFile(string $filePath, string $section, string $container, string $modelName): void
    {
        $tableName = $this->getTableName($modelName);
        $morphClass = strtolower($modelName);

        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Models;

use App\\Ship\\Parents\\Models\\Model as ParentModel;

final class {$modelName} extends ParentModel
{
    protected \$table = '{$tableName}';

    protected \$fillable = [
        'name',
        // TODO: Add your fillable fields here
    ];

    protected \$casts = [
        // TODO: Add your casts here
    ];

    //============================Scopes===========================//

    // TODO: Add your query scopes here

    //============================Relations===========================//

    // TODO: Add your relationships here

    //============================Methods===========================//

    public function getMorphClass(): string
    {
        return '{$morphClass}';
    }
}

PHP;

        File::put($filePath, $content);
        $this->line("  ✓ Created: Models/{$modelName}.php");
    }

    private function getTableName(string $modelName): string
    {
        return Str::snake(Str::plural($modelName));
    }
}
