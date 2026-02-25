<?php

namespace App\Console\Commands;

use App\Console\Commands\ModuleGeneration\ConfigurationParser;
use App\Console\Commands\ModuleGeneration\IntegrationManager;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use App\Console\Commands\ModuleGeneration\ModuleValidator;
use App\Console\Commands\ModuleGeneration\Generators\ModelGenerator;
use App\Console\Commands\ModuleGeneration\Generators\MigrationGenerator;
use App\Console\Commands\ModuleGeneration\Generators\SchemaGenerator;
use App\Console\Commands\ModuleGeneration\Generators\AuthorizerGenerator;
use App\Console\Commands\ModuleGeneration\Generators\RequestGenerator;
use App\Console\Commands\ModuleGeneration\Generators\ControllerGenerator;
use App\Console\Commands\ModuleGeneration\Generators\FactoryGenerator;
use App\Console\Commands\ModuleGeneration\Generators\TestGenerator;
use App\Console\Commands\ModuleGeneration\Generators\RouteGenerator;
use App\Console\Commands\ModuleGeneration\Generators\RouteServiceProviderGenerator;
use App\Console\Commands\ModuleGeneration\Generators\PermissionSeederGenerator;
use App\Console\Commands\ModuleGeneration\Generators\AssignPermissionSeederGenerator;
use App\Console\Commands\ModuleGeneration\Generators\DatabaseSeederGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class GenerateModuleCommand extends Command
{
    protected $signature = 'module:generate {name : Module name in PascalCase} {--config= : Path to JSON config file} {--force : Overwrite existing files}';

    protected $description = 'Generate a complete module from a JSON configuration file';

    private array $createdFiles = [];

    public function handle(): int
    {
        $moduleName = $this->argument('name');
        $configPath = $this->option('config');

        if (!$configPath) {
            $this->error('The --config option is required.');
            return self::FAILURE;
        }

        // 1. Parse config
        $this->info("Parsing configuration from {$configPath}...");
        try {
            $config = (new ConfigurationParser())->parse($configPath);
        } catch (\Exception $e) {
            $this->error("Config error: {$e->getMessage()}");
            return self::FAILURE;
        }

        // Validate module name matches config
        if ($config->name !== $moduleName) {
            $this->error("Module name '{$moduleName}' does not match config module '{$config->name}'");
            return self::FAILURE;
        }

        // 2. Validate no conflicts
        $this->info("Validating module structure...");
        try {
            $validator = new ModuleValidator($this);
            $validator->validateEntityNamesForConflicts(
                $this->buildValidatorEntities($config),
                $moduleName
            );
        } catch (\Exception $e) {
            if (!$this->option('force')) {
                $this->error("Validation error: {$e->getMessage()}");
                $this->warn("Use --force to override.");
                return self::FAILURE;
            }
            $this->warn("Validation warning (forced): {$e->getMessage()}");
        }

        // 3. Create base module structure
        $this->info("Creating base module structure...");
        $this->createBaseModule($moduleName);

        // 4. Generate entities (sorted by dependency)
        $entities = $config->getEntitiesSortedByDependency();
        $this->info("Generating " . count($entities) . " entities...");

        foreach ($entities as $index => $entity) {
            $this->info("  [{$entity->modelName}]");

            // Migration
            $migGen = new MigrationGenerator($index);
            $this->track($migGen->generate($config, $entity), 'Migration');

            // Model
            $this->track((new ModelGenerator())->generate($config, $entity), 'Model');

            // Schema
            $this->track((new SchemaGenerator())->generate($config, $entity), 'Schema');

            // Authorizer
            $this->track((new AuthorizerGenerator())->generate($config, $entity), 'Authorizer');

            // Request
            $this->track((new RequestGenerator())->generate($config, $entity), 'Request');

            // Controller
            $this->track((new ControllerGenerator())->generate($config, $entity), 'Controller');

            // Factory
            $this->track((new FactoryGenerator())->generate($config, $entity), 'Factory');

            // Tests (5 files)
            $testPaths = (new TestGenerator())->generate($config, $entity);
            foreach ($testPaths as $testPath) {
                $this->track($testPath, 'Test');
            }
        }

        // 5. Module-level files
        $this->info("Generating module-level files...");

        $this->track((new RouteGenerator())->generate($config), 'Routes');
        $this->track((new RouteServiceProviderGenerator())->generate($config), 'RouteServiceProvider');
        $this->track((new PermissionSeederGenerator())->generate($config), 'PermissionSeeder');
        $this->track((new AssignPermissionSeederGenerator())->generate($config), 'AssignPermissionsSeeder');
        $this->track((new DatabaseSeederGenerator())->generate($config), 'DatabaseSeeder');

        // 6. Integration (Server.php, DatabaseSeeder, TestCase)
        $this->info("Integrating module into application...");
        $integrationEntities = $this->buildIntegrationEntities($config);
        $integrationManager = new IntegrationManager($moduleName, $this);
        $integrationManager->integrateModuleCompletely($integrationEntities);

        // Also add to TestDatabaseSeeder.php for test support
        $this->addToTestDatabaseSeeder($moduleName);

        // 7. Summary
        $this->newLine();
        $this->info("Module '{$moduleName}' generated successfully!");
        $this->info("Files created: " . count($this->createdFiles));
        $this->newLine();

        $this->table(
            ['Type', 'Path'],
            array_map(fn($f) => [$f['type'], $this->shortenPath($f['path'])], $this->createdFiles)
        );

        $this->newLine();
        $this->info("Next steps:");
        $this->line("  1. php artisan migrate");
        $this->line("  2. php artisan test Modules/{$moduleName}/");
        $this->line("  3. Review generated code and add business logic where TODO comments are");

        return self::SUCCESS;
    }

    private function createBaseModule(string $name): void
    {
        $modulePath = base_path("Modules/{$name}");
        if (!File::isDirectory($modulePath)) {
            Artisan::call('module:make', ['name' => [$name]]);
            $this->info("  Base module created via module:make");
        } else {
            $this->warn("  Module directory already exists, skipping module:make");
        }

        // Fix composer.json: module:make generates lowercase 'database/' but
        // the actual directory is 'Database/' (capitalized). Fix the autoload paths.
        $this->fixModuleComposerJson($name);

        // Ensure required directories exist
        $dirs = [
            "Modules/{$name}/app/Models",
            "Modules/{$name}/app/JsonApi/V1",
            "Modules/{$name}/app/Http/Controllers/Api/V1",
            "Modules/{$name}/Database/migrations",
            "Modules/{$name}/Database/factories",
            "Modules/{$name}/Database/seeders",
            "Modules/{$name}/routes",
            "Modules/{$name}/tests/Feature",
        ];
        foreach ($dirs as $dir) {
            File::ensureDirectoryExists(base_path($dir));
        }
    }

    private function fixModuleComposerJson(string $name): void
    {
        $composerPath = base_path("Modules/{$name}/composer.json");
        if (!File::exists($composerPath)) return;

        $content = File::get($composerPath);
        $content = str_replace('database/factories/', 'Database/factories/', $content);
        $content = str_replace('database/seeders/', 'Database/seeders/', $content);
        File::put($composerPath, $content);
    }

    private function track(string $path, string $type): void
    {
        $this->createdFiles[] = ['type' => $type, 'path' => $path];
        $this->line("    + {$type}: " . $this->shortenPath($path));
    }

    private function shortenPath(string $path): string
    {
        return str_replace(base_path() . '/', '', $path);
    }

    /**
     * Build entities array in the format ModuleValidator expects
     */
    private function buildValidatorEntities(ModuleConfig $config): array
    {
        $entities = [];
        foreach ($config->entities as $entity) {
            $entities[$entity->modelName] = [
                'name' => $entity->modelName,
                'tableName' => $entity->tableName,
                'fields' => array_map(fn($f) => [
                    'name' => $f->dbName,
                    'type' => $f->type,
                ], $entity->fields),
            ];
        }
        return $entities;
    }

    /**
     * Build entities array in the format IntegrationManager expects
     */
    private function buildIntegrationEntities(ModuleConfig $config): array
    {
        $entities = [];
        foreach ($config->entities as $entity) {
            $entities[$entity->modelName] = [
                'name' => $entity->modelName,
            ];
        }
        return $entities;
    }

    /**
     * Add module to TestDatabaseSeeder's requiredModules array
     */
    private function addToTestDatabaseSeeder(string $moduleName): void
    {
        $path = base_path('database/seeders/TestDatabaseSeeder.php');
        if (!File::exists($path)) return;

        $content = File::get($path);

        // Check if already present
        if (str_contains($content, "'{$moduleName}'")) {
            $this->info("  TestDatabaseSeeder already contains {$moduleName}");
            return;
        }

        // Find the closing bracket of the $requiredModules array and insert before it
        $pattern = "/(protected array \\\$requiredModules = \[.*?)(    \];)/s";
        if (preg_match($pattern, $content, $matches)) {
            $replacement = $matches[1] . "        '{$moduleName}',\n" . $matches[2];
            $content = preg_replace($pattern, $replacement, $content);
            File::put($path, $content);
            $this->info("  Added {$moduleName} to TestDatabaseSeeder");
        }
    }
}
