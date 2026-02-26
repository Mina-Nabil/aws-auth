<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AddFunctionalTestCommand extends Command
{
    protected $signature = 'safetyshield:add:functionaltest {name? : The test name} {--section= : The section name} {--container= : The container name} {--db-handling= : Database handling (transaction, refresh, lazily)}';

    protected $description = 'Add a new Functional Test to an existing container';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Functional Test Generator');
        $this->newLine();

        // Get test name
        $name = $this->argument('name') ?? $this->ask('Enter test name (e.g., CreateProductTest, ListProductsTest)');

        if (empty($name)) {
            $this->error('Test name is required!');
            return self::FAILURE;
        }

        $testName = Str::studly($name);
        if (!Str::endsWith($testName, 'Test')) {
            $testName .= 'Test';
        }

        // Get section and container
        $section = $this->option('section') ?? $this->ask('Enter section name', 'AppSection');
        $container = $this->option('container') ?? $this->ask('Enter container name');

        if (empty($container)) {
            $this->error('Container name is required!');
            return self::FAILURE;
        }

        // Determine database handling strategy
        $dbHandling = $this->option('db-handling') ?? $this->choice(
            'Choose database handling strategy',
            ['transaction', 'refresh', 'lazily'],
            0  // Default to transaction
        );

        $containerName = Str::studly($container);
        $containerPath = app_path("Containers/{$section}/{$containerName}");

        // Check if container exists
        if (!File::exists($containerPath)) {
            $this->error("Container '{$containerName}' does not exist in '{$section}' section!");
            return self::FAILURE;
        }

        $testsPath = "{$containerPath}/Tests/Functional/API";
        if (!File::exists($testsPath)) {
            File::makeDirectory($testsPath, 0755, true);
        }

        $testFilePath = "{$testsPath}/{$testName}.php";

        // Check if test already exists
        if (File::exists($testFilePath)) {
            $this->error("Test '{$testName}' already exists!");
            return self::FAILURE;
        }

        $this->createFunctionalTestFile($testFilePath, $section, $containerName, $testName, $dbHandling);

        $this->newLine();
        $this->info('✅ Functional test created successfully!');
        $this->info("📁 Location: {$testFilePath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Implement your API test methods");
        $this->line("   2. Run test: php artisan test {$testFilePath}");

        return self::SUCCESS;
    }

    private function createFunctionalTestFile(string $filePath, string $section, string $container, string $testName, string $dbHandling): void
    {
        // Determine which trait to use based on db handling
        $dbTrait = match($dbHandling) {
            'refresh' => 'use Illuminate\\Foundation\\Testing\\RefreshDatabase;',
            'lazily' => 'use Illuminate\\Foundation\\Testing\\LazilyRefreshDatabase;',
            'transaction' => '', // ApiTestCase->FunctionalTestCase->ContainerTestCase already has DatabaseTransactions
            default => '',
        };

        $traitUsage = match($dbHandling) {
            'refresh' => '    use RefreshDatabase;',
            'lazily' => '    use LazilyRefreshDatabase;',
            'transaction' => '    // Uses DatabaseTransactions from ContainerTestCase',
            default => '    // Uses DatabaseTransactions from ContainerTestCase',
        };

        $imports = $dbTrait ? "\n{$dbTrait}" : '';

        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Tests\\Functional\\API;

use App\\Containers\\{$section}\\{$container}\\API\\Controllers\\{$container}Controller;
use App\\Containers\\{$section}\\{$container}\\Tests\\Functional\\ApiTestCase;
use App\\Containers\\{$section}\\User\\Models\\User;{$imports}
use PHPUnit\\Framework\\Attributes\\CoversClass;

#[CoversClass({$container}Controller::class)]
final class {$testName} extends ApiTestCase
{
{$traitUsage}

    public function testExample(): void
    {
        \$user = User::factory()->superAdmin()->createOne();
        \$this->actingAs(\$user);

        // TODO: Implement your API test
        \$response = \$this->getJson('/api/v1/your-endpoint');

        \$response->assertOk();
    }
}

PHP;

        File::put($filePath, $content);
        $this->line("  ✓ Created: Tests/Functional/API/{$testName}.php");
    }
}
