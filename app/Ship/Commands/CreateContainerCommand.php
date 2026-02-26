<?php

namespace App\Ship\Commands;

use App\Ship\Parents\Commands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class CreateContainerCommand extends Command
{
    protected $signature = 'safetyshield:create:container';

    protected $description = 'Create a new container with complete folder structure and base files';

    public function handle(): int
    {
        $this->info('🚀 SafetyShield Container Generator');
        $this->newLine();

        // Ask for section and container name
        $section = $this->ask('Enter section name (e.g., AppSection)', 'AppSection');
        $container = $this->ask('Enter container name (e.g., Product)');

        if (empty($container)) {
            $this->error('Container name is required!');
            return self::FAILURE;
        }

        $containerName = Str::studly($container);
        $containerPath = app_path("Containers/{$section}/{$containerName}");

        // Check if container already exists
        if (File::exists($containerPath)) {
            $this->error("Container '{$containerName}' already exists in '{$section}' section!");
            return self::FAILURE;
        }

        $this->info("Creating container: {$section}/{$containerName}");
        $this->newLine();

        // Create folder structure
        $this->createFolderStructure($containerPath, $section, $containerName);

        // Create files
        $this->createFiles($containerPath, $section, $containerName);

        $this->newLine();
        $this->info('✅ Container created successfully!');
        $this->info("📁 Location: {$containerPath}");
        $this->newLine();
        $this->line('📝 Next steps:');
        $this->line("   1. Update the migration file to define your table schema");
        $this->line("   2. Add permissions to permissions.json");
        $this->line("   3. Register gates in {$containerName}ServiceProvider");
        $this->line("   4. Implement your business logic in Actions and Tasks");
        $this->line("   5. Run migrations: php artisan migrate");

        return self::SUCCESS;
    }

    private function createFolderStructure(string $basePath, string $section, string $container): void
    {
        $folders = [
            'Actions',
            'API/Controllers',
            'API/Requests',
            'API/Routes',
            'API/Transformers',
            'Configs',
            'Data/DTOs',
            'Data/Factories',
            'Data/Migrations',
            'Data/Repositories',
            'Enums',
            'Models',
            'Policies',
            'Providers',
            'Tasks',
            'Tests/Functional/API',
            'Tests/Unit/Actions',
            'Tests/Unit/Tasks',
        ];

        foreach ($folders as $folder) {
            $path = "{$basePath}/{$folder}";
            File::makeDirectory($path, 0755, true);

            // Create .gitkeep to ensure folder is tracked by Git
            File::put("{$path}/.gitkeep", '');

            $this->line("  ✓ Created: {$folder}");
        }
    }

    private function createFiles(string $basePath, string $section, string $container): void
    {
        $this->newLine();
        $this->info('Creating files...');

        // README.md
        $this->createReadme($basePath, $section, $container);

        // permissions.json
        $this->createPermissionsJson($basePath, $container);

        // Policy
        $this->createPolicy($basePath, $section, $container);

        // ServiceProvider
        $this->createServiceProvider($basePath, $section, $container);

        // Model
        $this->createModel($basePath, $section, $container);

        // Gate Enum
        $this->createGateEnum($basePath, $section, $container);

        // Repository
        $this->createRepository($basePath, $section, $container);

        // Migration
        $this->createMigration($basePath, $section, $container);

        // Factory
        $this->createFactory($basePath, $section, $container);

        // DTO
        $this->createDTO($basePath, $section, $container);

        // Config
        $this->createConfig($basePath, $section, $container);

        // Transformer
        $this->createTransformer($basePath, $section, $container);

        // Routes
        $this->createRoutes($basePath, $section, $container);

        // Controller
        $this->createController($basePath, $section, $container);

        // composer.json
        $this->createComposerJson($basePath);

        // Test Cases
        $this->createTestCases($basePath, $section, $container);

        // Request Files
        $this->createRequests($basePath, $section, $container);
    }

    private function createReadme(string $basePath, string $section, string $container): void
    {
        $content = <<<MD
# {$container} Container

## Overview

This container handles all {$container}-related functionality.

## Features

- CRUD operations for {$container}
- Permission-based access control
- Audit trail support
- RESTful API endpoints

## Structure

```
{$container}/
├── Actions/           # Business logic orchestrators
├── API/
│   ├── Controllers/   # HTTP controllers
│   ├── Requests/      # Form request validation
│   ├── Routes/        # API routes
│   └── Transformers/  # Response transformers
├── Configs/           # Container-specific configuration
├── Data/
│   ├── DTOs/          # Data Transfer Objects
│   ├── Factories/     # Model factories
│   ├── Migrations/    # Database migrations
│   └── Repositories/  # Data access layer
├── Enums/             # Enums and constants
├── Models/            # Eloquent models
├── Policies/          # Authorization policies
├── Providers/         # Service providers
├── Tasks/             # Reusable tasks
└── Tests/             # Unit and functional tests
```

## API Endpoints

### List {$container}s
```
GET /v1/{strtolower({$container})}s
```

### Find {$container} by ID
```
GET /v1/{strtolower({$container})}s/{id}
```

### Create {$container}
```
POST /v1/{strtolower({$container})}s
```

### Update {$container}
```
PATCH /v1/{strtolower({$container})}s/{id}
```

### Delete {$container}
```
DELETE /v1/{strtolower({$container})}s/{id}
```

## Permissions

See `permissions.json` for the list of all permissions in this container.

## Configuration

See `Configs/{strtolower($section)}-{strtolower($container)}.php` for container-specific configuration options.

MD;

        File::put("{$basePath}/README.md", $content);
        $this->line("  ✓ Created: README.md");
    }

    private function createPermissionsJson(string $basePath, string $container): void
    {
        $lowerContainer = Str::lower($container);

        // Generate UUIDs for each permission
        $viewAnyUuid = generate_uuid();
        $viewMyUuid = generate_uuid();
        $createUuid = generate_uuid();
        $updateAnyUuid = generate_uuid();
        $updateMyUuid = generate_uuid();
        $deleteAnyUuid = generate_uuid();
        $deleteMyUuid = generate_uuid();

        $content = <<<JSON
[
    {
        "id": "{$viewAnyUuid}",
        "name": "view-any-{$lowerContainer}",
        "display_name": "View any {$lowerContainer}",
        "description": "View any {$lowerContainer} in the system",
        "group": "{$lowerContainer}s",
        "default_roles": ["super-admin", "admin"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "{$viewMyUuid}",
        "name": "view-my-{$lowerContainer}",
        "display_name": "View my {$lowerContainer}",
        "description": "View own {$lowerContainer}",
        "group": "{$lowerContainer}s",
        "default_roles": ["super-admin", "admin", "user"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "{$createUuid}",
        "name": "create-{$lowerContainer}",
        "display_name": "Create {$lowerContainer}",
        "description": "Create new {$lowerContainer}",
        "group": "{$lowerContainer}s",
        "default_roles": ["super-admin", "admin"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "{$updateAnyUuid}",
        "name": "update-any-{$lowerContainer}",
        "display_name": "Update any {$lowerContainer}",
        "description": "Update any {$lowerContainer} in the system",
        "group": "{$lowerContainer}s",
        "default_roles": ["super-admin", "admin"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "{$updateMyUuid}",
        "name": "update-my-{$lowerContainer}",
        "display_name": "Update my {$lowerContainer}",
        "description": "Update own {$lowerContainer}",
        "group": "{$lowerContainer}s",
        "default_roles": ["super-admin", "admin", "user"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "{$deleteAnyUuid}",
        "name": "delete-any-{$lowerContainer}",
        "display_name": "Delete any {$lowerContainer}",
        "description": "Delete any {$lowerContainer} in the system",
        "group": "{$lowerContainer}s",
        "default_roles": ["super-admin"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "{$deleteMyUuid}",
        "name": "delete-my-{$lowerContainer}",
        "display_name": "Delete my {$lowerContainer}",
        "description": "Delete own {$lowerContainer}",
        "group": "{$lowerContainer}s",
        "default_roles": ["super-admin", "admin"],
        "version": 1,
        "deprecated": false
    }
]

JSON;

        File::put("{$basePath}/permissions.json", $content);
        $this->line("  ✓ Created: permissions.json with auto-generated UUIDs");
    }

    private function createPolicy(string $basePath, string $section, string $container): void
    {
        $lowerContainer = strtolower($container);
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Policies;

use App\\Containers\\{$section}\\{$container}\\Models\\{$container};
use App\\Ship\\Parents\\Models\\UserModel as User;
use App\\Ship\\Parents\\Policies\\Policy as ParentPolicy;

final class {$container}Policy extends ParentPolicy
{
    public function viewAny(User \$user): bool
    {
        return \$user->hasPermissionTo('view-any-{$lowerContainer}');
    }

    public function view(User \$user, {$container} \$entity): bool
    {
        return \$user->hasPermissionTo('view-any-{$lowerContainer}')
            || (\$user->hasPermissionTo('view-my-{$lowerContainer}') && false); // TODO: Add specific permission check
    }

    public function create(User \$user): bool
    {
        return \$user->hasPermissionTo('create-{$lowerContainer}');
    }

    public function update(User \$user, {$container} \$entity): bool
    {
        return \$user->hasPermissionTo('update-any-{$lowerContainer}')
            || (\$user->hasPermissionTo('update-my-{$lowerContainer}') && false); // TODO: Add specific permission check
    }

    public function delete(User \$user, {$container} \$entity): bool
    {
        return \$user->hasPermissionTo('delete-any-{$lowerContainer}')
            || (\$user->hasPermissionTo('delete-my-{$lowerContainer}') && false); // TODO: Add specific permission check
    }
}

PHP;

        File::put("{$basePath}/Policies/{$container}Policy.php", $content);
        $this->line("  ✓ Created: Policies/{$container}Policy.php");
    }

    private function createServiceProvider(string $basePath, string $section, string $container): void
    {
        $snakeUpper = strtoupper(Str::snake($container));
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Providers;

use App\\Containers\\{$section}\\{$container}\\Enums\\{$container}Gate;
use App\\Containers\\{$section}\\{$container}\\Policies\\{$container}Policy;
use App\\Ship\\Parents\\Providers\\ServiceProvider as ParentServiceProvider;
use Illuminate\\Support\\Facades\\Gate;

final class {$container}ServiceProvider extends ParentServiceProvider
{
    public function boot(): void
    {
        // Register Gates
        Gate::define({$container}Gate::VIEW_ANY_{$snakeUpper}, [{$container}Policy::class, 'viewAny']);
        Gate::define({$container}Gate::VIEW_{$snakeUpper}, [{$container}Policy::class, 'view']);
        Gate::define({$container}Gate::CREATE_{$snakeUpper}, [{$container}Policy::class, 'create']);
        Gate::define({$container}Gate::UPDATE_{$snakeUpper}, [{$container}Policy::class, 'update']);
        Gate::define({$container}Gate::DELETE_{$snakeUpper}, [{$container}Policy::class, 'delete']);
    }
}

PHP;

        File::put("{$basePath}/Providers/{$container}ServiceProvider.php", $content);
        $this->line("  ✓ Created: Providers/{$container}ServiceProvider.php");
    }

    private function createModel(string $basePath, string $section, string $container): void
    {
        $tableName = Str::snake(Str::plural($container));
        $morphClass = strtolower($container);
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Models;

use App\\Ship\\Parents\\Models\\Model as ParentModel;

final class {$container} extends ParentModel
{
    protected \$table = '{$tableName}';

    protected \$fillable = [
        'name',
        // Add your fillable fields here
    ];

    protected \$casts = [
        // Add your casts here
    ];

    //============================Scopes===========================//

    // Add your query scopes here

    //============================Relations===========================//

    // Add your relationships here

    //============================Methods===========================//

    public function getMorphClass(): string
    {
        return '{$morphClass}';
    }
}

PHP;

        File::put("{$basePath}/Models/{$container}.php", $content);
        $this->line("  ✓ Created: Models/{$container}.php");
    }

    private function createGateEnum(string $basePath, string $section, string $container): void
    {
        $lowerContainer = Str::lower($container);
        $upperSnake = strtoupper(Str::snake($container));
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Enums;

enum {$container}Gate: string
{
    case VIEW_ANY_{$upperSnake} = 'view-any-{$lowerContainer}-gate';
    case VIEW_{$upperSnake} = 'view-{$lowerContainer}-gate';
    case CREATE_{$upperSnake} = 'create-{$lowerContainer}-gate';
    case UPDATE_{$upperSnake} = 'update-{$lowerContainer}-gate';
    case DELETE_{$upperSnake} = 'delete-{$lowerContainer}-gate';
}

PHP;

        File::put("{$basePath}/Enums/{$container}Gate.php", $content);
        $this->line("  ✓ Created: Enums/{$container}Gate.php");
    }

    private function createRepository(string $basePath, string $section, string $container): void
    {
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Data\\Repositories;

use App\\Containers\\{$section}\\{$container}\\Models\\{$container};
use App\\Ship\\Parents\\Repositories\\Repository as ParentRepository;

final class {$container}Repository extends ParentRepository
{
    protected \$fieldSearchable = [
        'name' => 'like',
        // Add searchable fields here
    ];

    public function loadQuery(): self
    {
        return \$this->scopeQuery(function (\$query) {
            // Add your query modifications here
            return \$query;
        });
    }

    public function model(): string
    {
        return {$container}::class;
    }
}

PHP;

        File::put("{$basePath}/Data/Repositories/{$container}Repository.php", $content);
        $this->line("  ✓ Created: Data/Repositories/{$container}Repository.php");
    }

    private function createMigration(string $basePath, string $section, string $container): void
    {
        $timestamp = date('Y_m_d_His');
        $tableName = Str::snake(Str::plural($container));
        $content = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('{$tableName}', static function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            
            // TODO: Add your table columns here
            
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

PHP;

        File::put("{$basePath}/Data/Migrations/{$timestamp}_create_{$tableName}_table.php", $content);
        $this->line("  ✓ Created: Data/Migrations/{$timestamp}_create_{$tableName}_table.php");
    }

    private function createFactory(string $basePath, string $section, string $container): void
    {
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Data\\Factories;

use App\\Containers\\{$section}\\{$container}\\Models\\{$container};
use App\\Ship\\Parents\\Factories\\Factory as ParentFactory;

/**
 * @template TModel of {$container}
 *
 * @extends ParentFactory<TModel>
 */
final class {$container}Factory extends ParentFactory
{
    /** @var class-string<TModel> */
    protected \$model = {$container}::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            // TODO: Add your factory fields here
        ];
    }
}

PHP;

        File::put("{$basePath}/Data/Factories/{$container}Factory.php", $content);
        $this->line("  ✓ Created: Data/Factories/{$container}Factory.php");
    }

    private function createDTO(string $basePath, string $section, string $container): void
    {
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Data\\DTOs;

use App\\Ship\\Parents\\DTOs\\DTO;

final readonly class {$container}DTO extends DTO
{
    public function __construct(
        public ?string \$id = null,
        public ?string \$name = null,
        // TODO: Add your DTO properties here
    ) {
    }

    public static function fromArray(array \$data): self
    {
        return new self(
            id: \$data['id'] ?? null,
            name: \$data['name'] ?? null,
            // TODO: Map your array fields here
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => \$this->id,
            'name' => \$this->name,
            // TODO: Add your fields here
        ], fn (\$value) => \$value !== null);
    }
}

PHP;

        File::put("{$basePath}/Data/DTOs/{$container}DTO.php", $content);
        $this->line("  ✓ Created: Data/DTOs/{$container}DTO.php");
    }

    private function createConfig(string $basePath, string $section, string $container): void
    {
        $configName = Str::lower($section) . '-' . Str::lower($container);
        $content = <<<PHP
<?php

return [
    // Add your container-specific configuration here
    
    // 'example_setting' => env('{strtoupper(Str::snake($container))}_EXAMPLE', 'default_value'),
];

PHP;

        File::put("{$basePath}/Configs/{$configName}.php", $content);
        $this->line("  ✓ Created: Configs/{$configName}.php");
    }

    private function createTransformer(string $basePath, string $section, string $container): void
    {
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\API\\Transformers;

use App\\Containers\\{$section}\\{$container}\\Models\\{$container};
use App\\Ship\\Parents\\Transformers\\Transformer as ParentTransformer;

final class {$container}Transformer extends ParentTransformer
{
    protected array \$availableIncludes = [
        // Add available includes here
    ];

    protected array \$defaultIncludes = [];

    public function transform({$container} \$entity): array
    {
        return [
            'id' => \$entity->id,
            'name' => \$entity->name,
            // TODO: Add your transformation fields here
            'created_at' => \$entity->created_at,
            'updated_at' => \$entity->updated_at,
        ];
    }
}

PHP;

        File::put("{$basePath}/API/Transformers/{$container}Transformer.php", $content);
        $this->line("  ✓ Created: API/Transformers/{$container}Transformer.php");
    }

    private function createRoutes(string $basePath, string $section, string $container): void
    {
        $lowerContainer = Str::lower($container);
        $pluralContainer = Str::plural($lowerContainer);
        $content = <<<PHP
<?php

use App\\Containers\\{$section}\\{$container}\\API\\Controllers\\{$container}Controller;
use Illuminate\\Support\\Facades\\Route;

Route::group(['prefix' => '{$pluralContainer}'], function () {
    
    Route::get('/', [{$container}Controller::class, 'index'])
        ->when(config('appSection-authentication.authentication_enabled'), fn (\$r) => \$r->middleware(['auth:api']));
    
    Route::get('/{id}', [{$container}Controller::class, 'show'])
        ->when(config('appSection-authentication.authentication_enabled'), fn (\$r) => \$r->middleware(['auth:api']));
    
    Route::post('/', [{$container}Controller::class, 'store'])
        ->when(config('appSection-authentication.authentication_enabled'), fn (\$r) => \$r->middleware(['auth:api']));
    
    Route::patch('/{id}', [{$container}Controller::class, 'update'])
        ->when(config('appSection-authentication.authentication_enabled'), fn (\$r) => \$r->middleware(['auth:api']));
    
    Route::delete('/{id}', [{$container}Controller::class, 'destroy'])
        ->when(config('appSection-authentication.authentication_enabled'), fn (\$r) => \$r->middleware(['auth:api']));
});

PHP;

        File::put("{$basePath}/API/Routes/{$container}CRUD.v1.private.php", $content);
        $this->line("  ✓ Created: API/Routes/{$container}CRUD.v1.private.php");
    }

    private function createController(string $basePath, string $section, string $container): void
    {
        $content = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\API\\Controllers;

use App\\Containers\\{$section}\\{$container}\\API\\Requests\\List{$container}sRequest;
use App\\Containers\\{$section}\\{$container}\\API\\Requests\\Show{$container}Request;
use App\\Containers\\{$section}\\{$container}\\API\\Requests\\Create{$container}Request;
use App\\Containers\\{$section}\\{$container}\\API\\Requests\\Update{$container}Request;
use App\\Containers\\{$section}\\{$container}\\API\\Requests\\Delete{$container}Request;
use App\\Ship\\Parents\\Controllers\\ApiController;
use Illuminate\\Http\\JsonResponse;
use Apiato\\Support\\Facades\\Response;

final class {$container}Controller extends ApiController
{
    public function index(List{$container}sRequest \$request): JsonResponse
    {
        // TODO: Implement list {$container}s
        return Response::json(['message' => 'List {$container}s']);
    }

    public function show(Show{$container}Request \$request, string \$id): JsonResponse
    {
        // TODO: Implement show {$container}
        return Response::json(['message' => "Show {$container}: {\$id}"]);
    }

    public function store(Create{$container}Request \$request): JsonResponse
    {
        // TODO: Implement create {$container}
        return Response::json(['message' => 'Create {$container}'], 201);
    }

    public function update(Update{$container}Request \$request, string \$id): JsonResponse
    {
        // TODO: Implement update {$container}
        return Response::json(['message' => "Update {$container}: {\$id}"]);
    }

    public function destroy(Delete{$container}Request \$request, string \$id): JsonResponse
    {
        // TODO: Implement delete {$container}
        return Response::noContent();
    }
}

PHP;

        File::put("{$basePath}/API/Controllers/{$container}Controller.php", $content);
        $this->line("  ✓ Created: API/Controllers/{$container}Controller.php");
    }

    private function createComposerJson(string $basePath): void
    {
        $content = <<<JSON
{
    "name": "container/composer",
    "description": "Container composer file",
    "type": "library",
    "require": {}
}

JSON;

        File::put("{$basePath}/composer.json", $content);
        $this->line("  ✓ Created: composer.json");
    }

    private function createTestCases(string $basePath, string $section, string $container): void
    {
        // UnitTestCase.php
        $unitTestCase = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Tests;

use App\\Ship\\Parents\\Tests\\ContainerTestCase;

abstract class UnitTestCase extends ContainerTestCase
{
    // Add container-specific test helpers here
}

PHP;

        File::put("{$basePath}/Tests/UnitTestCase.php", $unitTestCase);
        $this->line("  ✓ Created: Tests/UnitTestCase.php");

        // FunctionalTestCase.php
        $functionalTestCase = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Tests;

use App\\Ship\\Parents\\Tests\\ContainerTestCase;

abstract class FunctionalTestCase extends ContainerTestCase
{
    // Add container-specific functional test helpers here
}

PHP;

        File::put("{$basePath}/Tests/FunctionalTestCase.php", $functionalTestCase);
        $this->line("  ✓ Created: Tests/FunctionalTestCase.php");

        // Functional/ApiTestCase.php
        $apiTestCase = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\Tests\\Functional;

use App\\Containers\\{$section}\\{$container}\\Tests\\FunctionalTestCase;

class ApiTestCase extends FunctionalTestCase
{
    // Add API-specific test helpers here
}

PHP;

        File::put("{$basePath}/Tests/Functional/ApiTestCase.php", $apiTestCase);
        $this->line("  ✓ Created: Tests/Functional/ApiTestCase.php");
    }

    private function createRequests(string $basePath, string $section, string $container): void
    {
        $lowerContainer = strtolower($container);
        $upperSnake = strtoupper(Str::snake($container));

        // List Request
        $listRequest = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\API\\Requests;

use App\\Containers\\{$section}\\{$container}\\Enums\\{$container}Gate;
use App\\Ship\\Parents\\Requests\\Request as ParentRequest;
use Illuminate\\Support\\Facades\\Gate;

final class List{$container}sRequest extends ParentRequest
{
    public function rules(): array
    {
        return [
            // Add your query parameter validation rules here
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows({$container}Gate::VIEW_ANY_{$upperSnake});
    }
}

PHP;

        // Show Request
        $showRequest = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\API\\Requests;

use App\\Containers\\{$section}\\{$container}\\Enums\\{$container}Gate;
use App\\Ship\\Parents\\Requests\\Request as ParentRequest;
use Illuminate\\Support\\Facades\\Gate;

final class Show{$container}Request extends ParentRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:{Str::snake(Str::plural($container))},id',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows({$container}Gate::VIEW_{$upperSnake});
    }
}

PHP;

        // Create Request
        $createRequest = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\API\\Requests;

use App\\Containers\\{$section}\\{$container}\\Enums\\{$container}Gate;
use App\\Ship\\Parents\\Requests\\Request as ParentRequest;
use Illuminate\\Support\\Facades\\Gate;

final class Create{$container}Request extends ParentRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // TODO: Add your validation rules here
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows({$container}Gate::CREATE_{$upperSnake});
    }
}

PHP;

        // Update Request
        $updateRequest = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\API\\Requests;

use App\\Containers\\{$section}\\{$container}\\Enums\\{$container}Gate;
use App\\Ship\\Parents\\Requests\\Request as ParentRequest;
use Illuminate\\Support\\Facades\\Gate;

final class Update{$container}Request extends ParentRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            // TODO: Add your validation rules here
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows({$container}Gate::UPDATE_{$upperSnake});
    }
}

PHP;

        // Delete Request
        $deleteRequest = <<<PHP
<?php

namespace App\\Containers\\{$section}\\{$container}\\API\\Requests;

use App\\Containers\\{$section}\\{$container}\\Enums\\{$container}Gate;
use App\\Ship\\Parents\\Requests\\Request as ParentRequest;
use Illuminate\\Support\\Facades\\Gate;

final class Delete{$container}Request extends ParentRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|exists:{Str::snake(Str::plural($container))},id',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows({$container}Gate::DELETE_{$upperSnake});
    }
}

PHP;

        // Save all request files
        File::put("{$basePath}/API/Requests/List{$container}sRequest.php", $listRequest);
        $this->line("  ✓ Created: API/Requests/List{$container}sRequest.php");

        File::put("{$basePath}/API/Requests/Show{$container}Request.php", $showRequest);
        $this->line("  ✓ Created: API/Requests/Show{$container}Request.php");

        File::put("{$basePath}/API/Requests/Create{$container}Request.php", $createRequest);
        $this->line("  ✓ Created: API/Requests/Create{$container}Request.php");

        File::put("{$basePath}/API/Requests/Update{$container}Request.php", $updateRequest);
        $this->line("  ✓ Created: API/Requests/Update{$container}Request.php");

        File::put("{$basePath}/API/Requests/Delete{$container}Request.php", $deleteRequest);
        $this->line("  ✓ Created: API/Requests/Delete{$container}Request.php");
    }
}
