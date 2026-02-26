<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AddRequestCommand extends Command
{
    protected $signature = 'safetyshield:add:request {name? : The request name} {--section= : The section name} {--container= : The container name}';

    protected $description = 'Add a new Request to an existing container';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Request Generator');
        $this->newLine();

        // Get request name
        $name = $this->argument('name') ?? $this->ask('Enter request name (e.g., CreateProduct, UpdateProduct)');

        if (empty($name)) {
            $this->error('Request name is required!');
            return self::FAILURE;
        }

        $requestName = Str::studly($name);
        if (!Str::endsWith($requestName, 'Request')) {
            $requestName .= 'Request';
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

        $requestsPath = "{$containerPath}/API/Requests";
        if (!File::exists($requestsPath)) {
            File::makeDirectory($requestsPath, 0755, true);
        }

        $requestFilePath = "{$requestsPath}/{$requestName}.php";

        // Check if request already exists
        if (File::exists($requestFilePath)) {
            $this->error("Request '{$requestName}' already exists!");
            return self::FAILURE;
        }

        $this->createRequestFile($requestFilePath, $section, $containerName, $requestName);

        $this->newLine();
        $this->info('✅ Request created successfully!');
        $this->info("📁 Location: {$requestFilePath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Define validation rules in rules() method");
        $this->line("   2. Update authorize() method with proper gate");
        $this->line("   3. Use in controller method");

        return self::SUCCESS;
    }

    private function createRequestFile(string $filePath, string $section, string $container, string $requestName): void
    {
        $upperSnake = strtoupper(Str::snake($container));
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\API\\Requests;

use App\\Containers\\{$section}\\{$container}\\Enums\\{$container}Gate;
use App\\Ship\\Parents\\Requests\\Request as ParentRequest;
use Illuminate\\Support\\Facades\\Gate;

final class {$requestName} extends ParentRequest
{
    public function rules(): array
    {
        return [
            // TODO: Add your validation rules here
            'name' => 'required|string|max:255',
        ];
    }

    public function authorize(): bool
    {
        // TODO: Update with appropriate gate
        return Gate::allows({$container}Gate::CREATE_{$upperSnake});
    }
}

PHP;

        File::put($filePath, $content);
        $this->line("  ✓ Created: API/Requests/{$requestName}.php");
    }
}
