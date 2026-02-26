<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AddMigrationCommand extends Command
{
    protected $signature = 'safetyshield:add:migration {name? : The migration name} {--section= : The section name} {--container= : The container name}';

    protected $description = 'Add a new Migration to an existing container';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Migration Generator');
        $this->newLine();

        // Get migration name
        $name = $this->argument('name') ?? $this->ask('Enter migration name (e.g., create_products_table, add_status_to_orders)');

        if (empty($name)) {
            $this->error('Migration name is required!');
            return self::FAILURE;
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
            return self::FAILURE;
        }

        // Create migrations directory if needed
        $migrationsPath = "{$containerPath}/Data/Migrations";
        if (!File::exists($migrationsPath)) {
            File::makeDirectory($migrationsPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $migrationName = Str::snake($name);
        $migrationFilePath = "{$migrationsPath}/{$timestamp}_{$migrationName}.php";

        $this->createMigrationFile($migrationFilePath, $migrationName);

        $this->newLine();
        $this->info('✅ Migration created successfully!');
        $this->info("📁 Location: {$migrationFilePath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Define your schema in the up() method");
        $this->line("   2. Run migration: php artisan migrate");

        return self::SUCCESS;
    }

    private function createMigrationFile(string $filePath, string $migrationName): void
    {
        $content = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // TODO: Define your schema here
    }

    public function down(): void
    {
        // TODO: Define rollback logic here
    }
};

PHP;

        File::put($filePath, $content);
        $this->line("  ✓ Created: Data/Migrations/{$migrationName}.php");
    }
}
