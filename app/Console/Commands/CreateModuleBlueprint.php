<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;

class CreateModuleBlueprint extends Command
{
    protected $signature = 'module:blueprint {name} {fields} {--model=} {--table=}';
    protected $description = 'Create a complete module following the blueprint master standards';

    public function handle()
    {
        $moduleName = $this->argument('name');
        $fieldsString = $this->argument('fields');
        $modelName = $this->option('model') ?: rtrim($moduleName, 's');
        $tableName = $this->option('table') ?: Str::snake(Str::plural($modelName));
        $fields = $this->parseFields($fieldsString);

        $this->info("Creating module: {$moduleName}");
        $this->info("Model: {$modelName}");
        $this->info("Table: {$tableName}");
        $this->info("Fields: " . collect($fields)->pluck('name')->implode(', '));

        // 1. Create base module
        $this->call('module:make', ['name' => [$moduleName]]);

        // 2. Generate all components
        $this->generateModel($moduleName, $modelName, $tableName, $fields);
        $this->generateMigration($moduleName, $modelName, $tableName, $fields);
        $this->generateFactory($moduleName, $modelName, $fields);
        $this->generateSeeder($moduleName, $modelName);
        $this->generateJsonApiComponents($moduleName, $modelName, $fields);
        $this->generateTests($moduleName, $modelName);
        $this->generateDocumentation($moduleName, $modelName);
        $this->updateConfigurations($moduleName, $modelName);

        $this->info("✅ Module {$moduleName} created successfully!");
        $this->info("📋 Next steps:");
        $this->info("1. Review generated files and customize as needed");
        $this->info("2. Run: php artisan migrate");
        $this->info("3. Run: php artisan db:seed");
        $this->info("4. Run: php artisan test --filter {$modelName}");
    }

    private function parseFields(string $fieldsString): array
    {
        if (empty($fieldsString)) {
            return [
                ['name' => 'name', 'type' => 'string', 'rules' => 'required|string|max:255'],
                ['name' => 'description', 'type' => 'text', 'rules' => 'nullable|string'],
                ['name' => 'is_active', 'type' => 'boolean', 'rules' => 'boolean'],
            ];
        }

        $fields = [];
        foreach (explode(',', $fieldsString) as $field) {
            $parts = explode(':', $field);
            $fields[] = [
                'name' => $parts[0],
                'type' => $parts[1] ?? 'string',
                'rules' => $parts[2] ?? 'required|string'
            ];
        }

        return $fields;
    }

    private function generateModel(string $moduleName, string $modelName, string $tableName, array $fields)
    {
        $this->info("Generating model...");
        
        $fillable = collect($fields)->pluck('name')->map(fn($f) => "'$f'")->join(', ');
        $casts = collect($fields)->filter(fn($f) => in_array($f['type'], ['boolean', 'array', 'json']))
            ->map(fn($f) => "'{$f['name']}' => '{$f['type']}'")
            ->join(', ');

        $modelTemplate = $this->getStub('model');
        $modelContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{tableName}}',
            '{{fillable}}',
            '{{casts}}'
        ], [
            $moduleName,
            $modelName,
            $tableName,
            $fillable,
            $casts ? "'$casts'" : ''
        ], $modelTemplate);

        $modelPath = base_path("Modules/{$moduleName}/app/Models/{$modelName}.php");
        File::put($modelPath, $modelContent);
    }

    private function generateMigration(string $moduleName, string $modelName, string $tableName, array $fields)
    {
        $this->info("Generating migration...");
        
        $migrationFields = collect($fields)->map(function($field) {
            $line = "\$table->{$field['type']}('{$field['name']}')";
            if ($field['type'] === 'string') {
                $line .= "->nullable()";
            }
            return "            $line;";
        })->join("\n");

        $migrationTemplate = $this->getStub('migration');
        $migrationContent = str_replace([
            '{{tableName}}',
            '{{fields}}'
        ], [
            $tableName,
            $migrationFields
        ], $migrationTemplate);

        $timestamp = date('Y_m_d_His');
        $migrationPath = base_path("Modules/{$moduleName}/Database/Migrations/{$timestamp}_create_{$tableName}_table.php");
        File::put($migrationPath, $migrationContent);
    }

    private function generateFactory(string $moduleName, string $modelName, array $fields)
    {
        $this->info("Generating factory...");
        
        $factoryFields = collect($fields)->map(function($field) {
            switch ($field['type']) {
                case 'string':
                    return "'{$field['name']}' => fake()->words(3, true),";
                case 'text':
                    return "'{$field['name']}' => fake()->paragraph(),";
                case 'boolean':
                    return "'{$field['name']}' => fake()->boolean(80),";
                case 'decimal':
                    return "'{$field['name']}' => fake()->randomFloat(2, 10, 1000),";
                case 'integer':
                    return "'{$field['name']}' => fake()->numberBetween(1, 100),";
                default:
                    return "'{$field['name']}' => fake()->word(),";
            }
        })->map(fn($line) => "            $line")->join("\n");

        $factoryTemplate = $this->getStub('factory');
        $factoryContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{fields}}'
        ], [
            $moduleName,
            $modelName,
            $factoryFields
        ], $factoryTemplate);

        $factoryPath = base_path("Modules/{$moduleName}/Database/Factories/{$modelName}Factory.php");
        File::put($factoryPath, $factoryContent);
    }

    private function generateJsonApiComponents(string $moduleName, string $modelName, array $fields)
    {
        $this->info("Generating JSON API components...");
        
        $modelPlural = Str::plural($modelName);
        $resourceType = Str::kebab($modelPlural);
        
        // Create directories
        $jsonApiPath = base_path("Modules/{$moduleName}/app/JsonApi/V1/{$modelPlural}");
        File::makeDirectory($jsonApiPath, 0755, true);

        // Generate Schema
        $this->generateSchema($moduleName, $modelName, $fields, $jsonApiPath);
        
        // Generate Resource
        $this->generateResource($moduleName, $modelName, $fields, $jsonApiPath);
        
        // Generate Authorizer
        $this->generateAuthorizer($moduleName, $modelName, $jsonApiPath);
        
        // Generate Request
        $this->generateRequest($moduleName, $modelName, $fields, $jsonApiPath);
    }

    private function generateSchema(string $moduleName, string $modelName, array $fields, string $path)
    {
        $schemaFields = collect($fields)->map(function($field) {
            switch ($field['type']) {
                case 'string':
                case 'text':
                    return "Str::make('{$field['name']}')->sortable(),";
                case 'boolean':
                    return "Boolean::make('{$field['name']}')->sortable(),";
                case 'decimal':
                case 'integer':
                    return "Number::make('{$field['name']}')->sortable(),";
                case 'json':
                case 'array':
                    return "ArrayHash::make('{$field['name']}'),";
                default:
                    return "Str::make('{$field['name']}'),";
            }
        })->map(fn($line) => "            $line")->join("\n");

        $schemaTemplate = $this->getStub('schema');
        $schemaContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{modelPlural}}',
            '{{fields}}'
        ], [
            $moduleName,
            $modelName,
            Str::plural($modelName),
            $schemaFields
        ], $schemaTemplate);

        File::put($path . "/{$modelName}Schema.php", $schemaContent);
    }

    private function generateResource(string $moduleName, string $modelName, array $fields, string $path)
    {
        $resourceFields = collect($fields)->map(function($field) {
            return "'{$field['name']}' => \$this->{$field['name']},";
        })->map(fn($line) => "            $line")->join("\n");

        $resourceTemplate = $this->getStub('resource');
        $resourceContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{modelPlural}}',
            '{{fields}}'
        ], [
            $moduleName,
            $modelName,
            Str::plural($modelName),
            $resourceFields
        ], $resourceTemplate);

        File::put($path . "/{$modelName}Resource.php", $resourceContent);
    }

    private function generateAuthorizer(string $moduleName, string $modelName, string $path)
    {
        $moduleSnake = Str::snake($moduleName);
        $modelSnake = Str::snake($modelName);
        
        $authorizerTemplate = $this->getStub('authorizer');
        $authorizerContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{modelPlural}}',
            '{{permissionPrefix}}'
        ], [
            $moduleName,
            $modelName,
            Str::plural($modelName),
            "{$moduleSnake}.{$modelSnake}"
        ], $authorizerTemplate);

        File::put($path . "/{$modelName}Authorizer.php", $authorizerContent);
    }

    private function generateRequest(string $moduleName, string $modelName, array $fields, string $path)
    {
        $rules = collect($fields)->map(function($field) {
            return "'{$field['name']}' => ['" . str_replace('|', "', '", $field['rules']) . "'],";
        })->map(fn($line) => "            $line")->join("\n");

        $requestTemplate = $this->getStub('request');
        $requestContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{modelPlural}}',
            '{{rules}}'
        ], [
            $moduleName,
            $modelName,
            Str::plural($modelName),
            $rules
        ], $requestTemplate);

        File::put($path . "/{$modelName}Request.php", $requestContent);
    }

    private function generateTests(string $moduleName, string $modelName)
    {
        $this->info("Generating tests...");
        
        $testTypes = ['Index', 'Show', 'Store', 'Update', 'Destroy'];
        
        foreach ($testTypes as $testType) {
            $testTemplate = $this->getStub("test-{$testType}");
            $testContent = str_replace([
                '{{moduleName}}',
                '{{modelName}}',
                '{{modelPlural}}',
                '{{resourceType}}'
            ], [
                $moduleName,
                $modelName,
                Str::plural($modelName),
                Str::kebab(Str::plural($modelName))
            ], $testTemplate);

            $testPath = base_path("Modules/{$moduleName}/Tests/Feature/{$modelName}{$testType}Test.php");
            File::makeDirectory(dirname($testPath), 0755, true);
            File::put($testPath, $testContent);
        }
    }

    private function generateSeeder(string $moduleName, string $modelName)
    {
        $this->info("Generating seeders...");
        
        $moduleSnake = Str::snake($moduleName);
        $modelSnake = Str::snake($modelName);
        
        // Permission seeder
        $permissionSeeder = $this->getStub('permission-seeder');
        $permissionContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{permissionPrefix}}'
        ], [
            $moduleName,
            $modelName,
            "{$moduleSnake}.{$modelSnake}"
        ], $permissionSeeder);

        $permissionPath = base_path("Modules/{$moduleName}/Database/Seeders/{$modelName}PermissionsSeeder.php");
        File::makeDirectory(dirname($permissionPath), 0755, true);
        File::put($permissionPath, $permissionContent);

        // Database seeder
        $databaseSeeder = $this->getStub('database-seeder');
        $databaseContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}'
        ], [
            $moduleName,
            $modelName
        ], $databaseSeeder);

        $databasePath = base_path("Modules/{$moduleName}/Database/Seeders/{$moduleName}DatabaseSeeder.php");
        File::put($databasePath, $databaseContent);
    }

    private function generateDocumentation(string $moduleName, string $modelName)
    {
        $this->info("Generating documentation...");
        
        // README.md
        $readmeTemplate = $this->getStub('readme');
        $readmeContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{modelPlural}}',
            '{{resourceType}}'
        ], [
            $moduleName,
            $modelName,
            Str::plural($modelName),
            Str::kebab(Str::plural($modelName))
        ], $readmeTemplate);

        File::put(base_path("Modules/{$moduleName}/README.md"), $readmeContent);

        // CHANGELOG.md
        $changelogTemplate = $this->getStub('changelog');
        $changelogContent = str_replace([
            '{{moduleName}}',
            '{{date}}'
        ], [
            $moduleName,
            now()->format('Y-m-d')
        ], $changelogTemplate);

        File::put(base_path("Modules/{$moduleName}/CHANGELOG.md"), $changelogContent);
    }

    private function updateConfigurations(string $moduleName, string $modelName)
    {
        $this->info("Updating configurations...");
        
        $this->line("⚠️  Manual steps required:");
        $this->line("1. Add to database/seeders/DatabaseSeeder.php:");
        $this->line("   \\Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}DatabaseSeeder::class,");
        
        $this->line("2. Add to tests/TestCase.php setUp():");
        $this->line("   \$this->artisan('module:seed', ['module' => '{$moduleName}']);");
        
        $this->line("3. Add to app/JsonApi/V1/Server.php allSchemas():");
        $this->line("   \\Modules\\{$moduleName}\\JsonApi\\V1\\{$modelName}s\\{$modelName}Schema::class,");
        
        $this->line("4. Add routes to RouteServiceProvider configureJsonApi():");
        $this->line("   \$server->resource('" . Str::kebab(Str::plural($modelName)) . "', \\Modules\\{$moduleName}\\JsonApi\\V1\\{$modelName}s\\{$modelName}Schema::class);");
    }

    private function getStub(string $stub): string
    {
        $stubPath = __DIR__ . "/stubs/module-blueprint/{$stub}.stub";
        
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }
        
        return File::get($stubPath);
    }
}
