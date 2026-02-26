<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AddRouteCommand extends Command
{
    protected $signature = 'safetyshield:add:route {name? : The route file name} {--section= : The section name} {--container= : The container name}';

    protected $description = 'Add a new Route file to an existing container';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Route Generator');
        $this->newLine();

        // Get route name
        $name = $this->argument('name') ?? $this->ask('Enter route file name (e.g., ProductCRUD, ProductAdmin)');

        if (empty($name)) {
            $this->error('Route file name is required!');
            return self::FAILURE;
        }

        $routeName = Str::studly($name);

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

        $routesPath = "{$containerPath}/API/Routes";
        if (!File::exists($routesPath)) {
            File::makeDirectory($routesPath, 0755, true);
        }

        $routeFilePath = "{$routesPath}/{$routeName}.v1.private.php";

        // Check if route file already exists
        if (File::exists($routeFilePath)) {
            $this->error("Route file '{$routeName}.v1.private.php' already exists!");
            return self::FAILURE;
        }

        $this->createRouteFile($routeFilePath, $section, $containerName, $routeName);

        $this->newLine();
        $this->info('✅ Route file created successfully!');
        $this->info("📁 Location: {$routeFilePath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Define your routes in the file");
        $this->line("   2. Routes are auto-discovered by Laravel");

        return self::SUCCESS;
    }

    private function createRouteFile(string $filePath, string $section, string $container, string $routeName): void
    {
        $prefix = Str::lower(Str::plural($container));
        $content = <<<PHP
<?php

use App\\Containers\\{$section}\\{$container}\\API\\Controllers\\{$container}Controller;
use Illuminate\\Support\\Facades\\Route;

Route::group(['prefix' => '{$prefix}'], function () {
    
    // TODO: Define your routes here
    
    Route::get('/', [{$container}Controller::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn (\$r) => \$r->middleware(['auth:api']));
    
});

PHP;

        File::put($filePath, $content);
        $this->line("  ✓ Created: API/Routes/{$routeName}.v1.private.php");
    }
}
