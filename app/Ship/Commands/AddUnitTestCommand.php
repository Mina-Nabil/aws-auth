<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class AddUnitTestCommand extends Command
{
    protected $signature = 'safetyshield:add:unittest {name? : The test name} {--section= : The section name} {--container= : The container name} {--type= : The type (action, task, request)} {--db-handling= : Database handling (transaction, refresh, lazily)}';

    protected $description = 'Add a new Unit Test to an existing container';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Unit Test Generator');
        $this->newLine();

        // Get test name
        $name = $this->argument('name') ?? $this->ask('Enter test name (e.g., CreateProductActionTest, FindProductTaskTest)');

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

        // Determine test type
        $type = $this->option('type') ?? $this->choice(
            'What are you testing?',
            ['action', 'task', 'request'],
            0
        );

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

        // Determine test path based on type
        $testSubPath = match($type) {
            'action' => 'Actions',
            'task' => 'Tasks',
            'request' => 'UI/API/Requests',
            default => 'Actions',
        };

        $testsPath = "{$containerPath}/Tests/Unit/{$testSubPath}";
        if (!File::exists($testsPath)) {
            File::makeDirectory($testsPath, 0755, true);
        }

        $testFilePath = "{$testsPath}/{$testName}.php";

        // Check if test already exists
        if (File::exists($testFilePath)) {
            $this->error("Test '{$testName}' already exists!");
            return self::FAILURE;
        }

        $this->createUnitTestFile($testFilePath, $section, $containerName, $testName, $type, $dbHandling);

        $this->newLine();
        $this->info('✅ Unit test created successfully!');
        $this->info("📁 Location: {$testFilePath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Implement your test methods");
        $this->line("   2. Run test: php artisan test {$testFilePath}");

        return self::SUCCESS;
    }

    private function createUnitTestFile(string $filePath, string $section, string $container, string $testName, string $type, string $dbHandling): void
    {
        $testSubPath = match($type) {
            'action' => 'Actions',
            'task' => 'Tasks',
            'request' => 'UI\\API\\Requests',
            default => 'Actions',
        };

        $classBeingTested = str_replace('Test', '', $testName);
        $classNamespace = match($type) {
            'action' => "App\\Containers\\{$section}\\{$container}\\Actions",
            'task' => "App\\Containers\\{$section}\\{$container}\\Tasks",
            'request' => "App\\Containers\\{$section}\\{$container}\\API\\Requests",
            default => "App\\Containers\\{$section}\\{$container}\\Actions",
        };

        // Determine which trait/parent to use based on db handling
        $dbTrait = match($dbHandling) {
            'refresh' => 'use Illuminate\\Foundation\\Testing\\RefreshDatabase;',
            'lazily' => 'use Illuminate\\Foundation\\Testing\\LazilyRefreshDatabase;',
            'transaction' => '', // ContainerTestCase already has DatabaseTransactions
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

namespace App\\Containers\\{$section}\\{$container}\\Tests\\Unit\\{$testSubPath};

use {$classNamespace}\\{$classBeingTested};
use App\\Containers\\{$section}\\{$container}\\Tests\\UnitTestCase;{$imports}
use PHPUnit\\Framework\\Attributes\\CoversClass;

#[CoversClass({$classBeingTested}::class)]
final class {$testName} extends UnitTestCase
{
{$traitUsage}

    public function testExample(): void
    {
        // TODO: Implement your test
        \$this->assertTrue(true);
    }
}

PHP;

        File::put($filePath, $content);
        $this->line("  ✓ Created: Tests/Unit/{$testSubPath}/{$testName}.php");
    }
}
