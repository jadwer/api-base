<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateAdvancedModuleBlueprint extends Command
{
    protected $signature = 'module:advanced-blueprint {module} {--config=}';
    protected $description = 'Create a module with multiple entities and complex relationships';
    
    private $entities = [];
    private $permissionsConfig = null;

    public function handle()
    {
        $moduleName = $this->argument('module');
        $configFile = $this->option('config');

        if ($configFile && File::exists($configFile)) {
            $config = json_decode(File::get($configFile), true);
            $this->createFromConfig($moduleName, $config);
        } else {
            $this->createInteractively($moduleName);
        }
    }

    private function createFromConfig(string $moduleName, array $config)
    {
        $this->info("🚀 Creating module from config: {$moduleName}");
        
        // Transform entities from object to array format expected by generator
        $entitiesConfig = $config['entities'] ?? [];
        $entities = [];
        
        foreach ($entitiesConfig as $entityName => $entityData) {
            $entity = [
                'name' => $entityData['name'],
                'tableName' => $entityData['tableName'],
                'fillable' => array_column($entityData['fields'], 'name'),
                'fields' => $entityData['fields'],
                'relationships' => []
            ];
            $entities[] = $entity;
        }
        
        $this->entities = $entities;
        $this->permissionsConfig = $config['permissions'] ?? null;
        $relationships = $config['relationships'] ?? [];
        
        $this->generateAdvancedModule($moduleName, $entities, $relationships);
    }

    private function createInteractively(string $moduleName)
    {
        $this->info("🚀 Creating advanced module interactively: {$moduleName}");
        
        $entities = [];
        $relationships = [];

        // Collect entities
        while (true) {
            $entityName = $this->ask('Enter entity name (or "done" to finish)');
            if ($entityName === 'done') break;

            $fields = $this->collectEntityFields($entityName);
            $entities[$entityName] = [
                'name' => $entityName,
                'fields' => $fields,
                'tableName' => Str::snake(Str::plural($entityName))
            ];

            $this->info("✅ Entity '{$entityName}' added with " . count($fields) . " fields");
        }

        // Collect relationships
        if (count($entities) > 1) {
            $this->info("\n🔗 Now let's define relationships between entities...");
            
            $entityNames = array_keys($entities);
            foreach ($entityNames as $entityA) {
                foreach ($entityNames as $entityB) {
                    if ($entityA >= $entityB) continue; // Avoid duplicates and self-relations
                    
                    $hasRelation = $this->confirm("Does '{$entityA}' have a relationship with '{$entityB}'?");
                    if ($hasRelation) {
                        $relationType = $this->choice(
                            "What type of relationship?",
                            ['one-to-one', 'one-to-many', 'many-to-many']
                        );
                        
                        $relationships[] = [
                            'entityA' => $entityA,
                            'entityB' => $entityB,
                            'type' => $relationType
                        ];
                        
                        $this->info("✅ {$relationType} relationship added: {$entityA} ↔ {$entityB}");
                    }
                }
            }
        }

        // Generate the module
        $this->generateAdvancedModule($moduleName, $entities, $relationships);
    }

    private function collectEntityFields(string $entityName): array
    {
        $fields = [];
        
        $this->info("Adding fields for '{$entityName}':");
        
        while (true) {
            $fieldName = $this->ask('Field name (or "done" to finish)');
            if ($fieldName === 'done') break;

            $fieldType = $this->choice('Field type', [
                'string', 'text', 'integer', 'bigInteger', 'decimal', 
                'boolean', 'date', 'datetime', 'timestamp', 'json'
            ]);

            $nullable = $this->confirm('Is nullable?', true);
            $unique = $this->confirm('Is unique?', false);

            $fields[] = [
                'name' => $fieldName,
                'type' => $fieldType,
                'nullable' => $nullable,
                'unique' => $unique
            ];
        }

        return $fields;
    }

    private function generateAdvancedModule(string $moduleName, array $entities, array $relationships)
    {
        // Create module structure
        $this->call('module:make', ['name' => [$moduleName]]);
        
        // Update composer.json to include JsonApi PSR-4 mapping
        $this->updateComposerJson($moduleName);
        
        // Generate each entity
        foreach ($entities as $entity) {
            $this->generateEntityFiles($moduleName, $entity, $relationships);
        }

        // Generate relationship migrations
        $this->generateRelationshipMigrations($moduleName, $relationships);
        
        // Generate JSON API routes
        $this->generateJsonApiRoutes($moduleName, $entities);
        
        // Update RouteServiceProvider for JSON API
        $this->generateRouteServiceProvider($moduleName);

        // Generate module documentation
        $this->generateModuleDocumentation($moduleName, $entities, $relationships);

        $this->info("✅ Advanced module '{$moduleName}' created successfully!");
        $this->showModuleSummary($moduleName, $entities, $relationships);
    }

    private function generateEntityFiles(string $moduleName, array $entity, array $relationships)
    {
        $entityName = $entity['name'];
        $this->info("📄 Generating files for {$entityName}...");

        // Model with relationships
        $this->generateAdvancedModel($moduleName, $entity, $relationships);
        
        // Migration
        $this->generateAdvancedMigration($moduleName, $entity, $relationships);
        
        // Factory
        $this->generateAdvancedFactory($moduleName, $entity);
        
        // JSON API Schema
        $this->generateAdvancedSchema($moduleName, $entity, $relationships);
        
        // JSON API Resource
        $this->generateAdvancedResource($moduleName, $entity, $relationships);
        
        // Authorizer
        $this->generateAdvancedAuthorizer($moduleName, $entity);
        
        // Request classes
        $this->generateAdvancedRequests($moduleName, $entity);
        
        // Controllers
        $this->generateAdvancedController($moduleName, $entity);
        
        // Tests
        $this->generateAdvancedTests($moduleName, $entity);
    }

    private function generateAdvancedModel(string $moduleName, array $entity, array $relationships)
    {
        $entityName = $entity['name'];
        $tableName = $entity['tableName'];
        
        // Generate fillable array
        $fillable = collect($entity['fields'])->pluck('name')->toArray();
        
        // Generate casts array
        $casts = collect($entity['fields'])->mapWithKeys(function($field) {
            $castMap = [
                'boolean' => 'boolean',
                'json' => 'array',
                'date' => 'date',
                'datetime' => 'datetime',
                'timestamp' => 'timestamp',
                'decimal' => 'decimal:2'
            ];
            
            if (isset($castMap[$field['type']])) {
                return [$field['name'] => $castMap[$field['type']]];
            }
            return [];
        })->toArray();

        // Generate relationships
        $relationshipMethods = $this->generateRelationshipMethods($entityName, $relationships);

        $modelTemplate = $this->getStub('advanced-model');
        $modelContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{tableName}}',
            '{{fillable}}',
            '{{casts}}',
            '{{relationships}}'
        ], [
            $moduleName,
            $entityName,
            $tableName,
            "'" . implode("', '", $fillable) . "'",
            $this->arrayToString($casts),
            $relationshipMethods
        ], $modelTemplate);

        $modelPath = base_path("Modules/{$moduleName}/app/Models/{$entityName}.php");
        
        // Create directory if it doesn't exist
        $modelDir = dirname($modelPath);
        if (!File::exists($modelDir)) {
            File::makeDirectory($modelDir, 0755, true);
        }
        
        File::put($modelPath, $modelContent);
    }

    private function generateRelationshipMethods(string $entityName, array $relationships): string
    {
        $methods = [];
        
        foreach ($relationships as $rel) {
            if ($rel['entityA'] === $entityName) {
                $relatedEntity = $rel['entityB'];
                $methodName = $this->getRelationshipMethodName($rel['type'], $relatedEntity, false);
                $method = $this->generateRelationshipMethod($rel['type'], $relatedEntity, $methodName, false);
                $methods[] = $method;
            } elseif ($rel['entityB'] === $entityName) {
                $relatedEntity = $rel['entityA'];
                $methodName = $this->getRelationshipMethodName($rel['type'], $relatedEntity, true);
                $method = $this->generateRelationshipMethod($rel['type'], $relatedEntity, $methodName, true);
                $methods[] = $method;
            }
        }
        
        return implode("\n\n", $methods);
    }

    private function getRelationshipMethodName(string $type, string $relatedEntity, bool $isReverse): string
    {
        switch ($type) {
            case 'one-to-one':
                return Str::camel($relatedEntity);
            case 'one-to-many':
                return $isReverse ? Str::camel($relatedEntity) : Str::camel(Str::plural($relatedEntity));
            case 'many-to-many':
                return Str::camel(Str::plural($relatedEntity));
            default:
                return Str::camel($relatedEntity);
        }
    }

    private function generateRelationshipMethod(string $type, string $relatedEntity, string $methodName, bool $isReverse): string
    {
        $relationshipMap = [
            'one-to-one' => $isReverse ? 'belongsTo' : 'hasOne',
            'one-to-many' => $isReverse ? 'belongsTo' : 'hasMany',
            'many-to-many' => 'belongsToMany'
        ];

        $laravelMethod = $relationshipMap[$type];
        
        $method = "    public function {$methodName}()\n    {\n";
        $method .= "        return \$this->{$laravelMethod}({$relatedEntity}::class);\n";
        $method .= "    }";
        
        return $method;
    }

    private function generateAdvancedMigration(string $moduleName, array $entity, array $relationships)
    {
        $entityName = $entity['name'];
        $tableName = $entity['tableName'];
        
        // Generate field definitions
        $fieldDefinitions = collect($entity['fields'])->map(function($field) {
            $line = "\$table->{$field['type']}('{$field['name']}')";
            
            if ($field['nullable']) {
                $line .= '->nullable()';
            }
            
            if ($field['unique']) {
                $line .= '->unique()';
            }
            
            return "            {$line};";
        })->join("\n");

        // Add foreign key fields for relationships
        $foreignKeys = $this->generateForeignKeyFields($entityName, $relationships);
        if ($foreignKeys) {
            $fieldDefinitions .= "\n\n            // Foreign keys\n" . $foreignKeys;
        }

        $migrationTemplate = $this->getStub('advanced-migration');
        $migrationContent = str_replace([
            '{{tableName}}',
            '{{fields}}'
        ], [
            $tableName,
            $fieldDefinitions
        ], $migrationTemplate);

        $timestamp = date('Y_m_d_His', time() + rand(1, 10));
        $migrationPath = base_path("Modules/{$moduleName}/Database/Migrations/{$timestamp}_create_{$tableName}_table.php");
        File::put($migrationPath, $migrationContent);
    }

    private function generateForeignKeyFields(string $entityName, array $relationships): string
    {
        $foreignKeys = [];
        
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'many-to-many') continue; // Handled in pivot table
            
            $needsForeignKey = false;
            $relatedEntity = '';
            
            if ($rel['entityA'] === $entityName && in_array($rel['type'], ['one-to-many', 'one-to-one'])) {
                // This entity is the "one" side, related entity needs the foreign key
                continue;
            } elseif ($rel['entityB'] === $entityName) {
                $needsForeignKey = true;
                $relatedEntity = $rel['entityA'];
            }
            
            if ($needsForeignKey) {
                $foreignKeyName = Str::snake($relatedEntity) . '_id';
                $relatedTable = Str::snake(Str::plural($relatedEntity));
                
                $foreignKeys[] = "            \$table->foreignId('{$foreignKeyName}')->constrained('{$relatedTable}')->onDelete('cascade');";
            }
        }
        
        return implode("\n", $foreignKeys);
    }

    private function generateRelationshipMigrations(string $moduleName, array $relationships)
    {
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'many-to-many') {
                $this->generatePivotMigration($moduleName, $rel);
            }
        }
    }

    private function generatePivotMigration(string $moduleName, array $relationship)
    {
        $entityA = $relationship['entityA'];
        $entityB = $relationship['entityB'];
        
        // Create alphabetically ordered table name
        $entities = [$entityA, $entityB];
        sort($entities);
        $tableName = Str::snake($entities[0]) . '_' . Str::snake($entities[1]);
        
        $foreignKeyA = Str::snake($entityA) . '_id';
        $foreignKeyB = Str::snake($entityB) . '_id';
        
        // Get actual table names from entities configuration
        $tableA = $this->getEntityTableName($moduleName, $entityA);
        $tableB = $this->getEntityTableName($moduleName, $entityB);

        $migrationTemplate = $this->getStub('pivot-migration');
        $migrationContent = str_replace([
            '{{tableName}}',
            '{{foreignKeyA}}',
            '{{foreignKeyB}}',
            '{{tableA}}',
            '{{tableB}}'
        ], [
            $tableName,
            $foreignKeyA,
            $foreignKeyB,
            $tableA,
            $tableB
        ], $migrationTemplate);

        $timestamp = date('Y_m_d_His', time() + rand(20, 30));
        $migrationPath = base_path("Modules/{$moduleName}/Database/Migrations/{$timestamp}_create_{$tableName}_table.php");
        File::put($migrationPath, $migrationContent);
        
        $this->info("🔗 Generated pivot table migration: {$tableName}");
    }
    
    private function generateJsonApiRoutes(string $moduleName, array $entities)
    {
        $this->info("🛣️ Generating JSON API routes...");
        
        // Generate api.php with simple comment
        $apiContent = "<?php\n\n";
        $apiContent .= "/*\n";
        $apiContent .= "|--------------------------------------------------------------------------\n";
        $apiContent .= "| API Routes\n";
        $apiContent .= "|--------------------------------------------------------------------------\n";
        $apiContent .= "|\n";
        $apiContent .= "| Here is where you can register API routes for your application. These\n";
        $apiContent .= "| routes are loaded by the RouteServiceProvider within a group which\n";
        $apiContent .= "| is assigned the \"api\" middleware group. Enjoy building your API!\n";
        $apiContent .= "|\n";
        $apiContent .= "*/\n\n";
        $apiContent .= "// Las rutas API del módulo {$moduleName} están configuradas en config/jsonapi.php\n";
        
        $apiRoutesPath = base_path("Modules/{$moduleName}/routes/api.php");
        File::put($apiRoutesPath, $apiContent);
        
        // Generate web.php with simple comment
        $webContent = "<?php\n\n";
        $webContent .= "/*\n";
        $webContent .= "|--------------------------------------------------------------------------\n";
        $webContent .= "| Web Routes\n";
        $webContent .= "|--------------------------------------------------------------------------\n";
        $webContent .= "|\n";
        $webContent .= "| Here is where you can register web routes for your application. These\n";
        $webContent .= "| routes are loaded by the RouteServiceProvider within a group which\n";
        $webContent .= "| contains the \"web\" middleware group. Now create something great!\n";
        $webContent .= "|\n";
        $webContent .= "*/\n\n";
        $webContent .= "// Web routes para el módulo {$moduleName} (si se necesitan)\n";
        
        $webRoutesPath = base_path("Modules/{$moduleName}/routes/web.php");
        File::put($webRoutesPath, $webContent);
        
        // Generate jsonapi.php with actual routes
        $jsonApiContent = "<?php\n\n";
        $jsonApiContent .= "use LaravelJsonApi\\Laravel\\Facades\\JsonApiRoute;\n";
        $jsonApiContent .= "use LaravelJsonApi\\Laravel\\Routing\\ResourceRegistrar;\n";
        
        // Add controller imports
        foreach ($entities as $entity) {
            $entityName = $entity['name'];
            $controllerName = $entityName . 'Controller';
            $jsonApiContent .= "use Modules\\{$moduleName}\\Http\\Controllers\\Api\\V1\\{$controllerName};\n";
        }
        
        $jsonApiContent .= "\nJsonApiRoute::server('v1')\n";
        $jsonApiContent .= "    ->prefix('v1')\n";
        $jsonApiContent .= "    ->middleware('auth:sanctum')\n";
        $jsonApiContent .= "    ->resources(function (ResourceRegistrar \$server) {\n";
        
        foreach ($entities as $entity) {
            $entityName = $entity['name'];
            $resourceType = Str::kebab(Str::plural($entityName));
            $controllerName = $entityName . 'Controller';
            
            $jsonApiContent .= "        \$server->resource('{$resourceType}', {$controllerName}::class);\n";
        }
        
        $jsonApiContent .= "    });\n";
        
        $jsonApiRoutesPath = base_path("Modules/{$moduleName}/routes/jsonapi.php");
        File::put($jsonApiRoutesPath, $jsonApiContent);
    }
    
    private function generateRouteServiceProvider(string $moduleName)
    {
        $this->info("🛣️ Generating RouteServiceProvider...");
        
        $template = $this->getStub('route-service-provider');
        $content = str_replace([
            '{{namespace}}',
            '{{className}}',
            '{{moduleName}}'
        ], [
            "Modules\\{$moduleName}\\Providers",
            'RouteServiceProvider',
            $moduleName
        ], $template);
        
        $providerPath = base_path("Modules/{$moduleName}/app/Providers/RouteServiceProvider.php");
        File::put($providerPath, $content);
    }

    private function generateAdvancedSchema(string $moduleName, array $entity, array $relationships)
    {
        $entityName = $entity['name'];
        $resourceType = Str::kebab(Str::plural($entityName));
        
        // Generate field definitions
        $fieldDefinitions = collect($entity['fields'])->map(function($field) {
            $schemaType = $this->mapToSchemaType($field['type']);
            return "            {$schemaType}::make('{$field['name']}'),";
        })->join("\n");

        // Generate relationship definitions
        $relationshipDefinitions = $this->generateSchemaRelationships($entityName, $relationships);
        if ($relationshipDefinitions) {
            $fieldDefinitions .= "\n\n            // Relationships\n" . $relationshipDefinitions;
        }

        $schemaTemplate = $this->getStub('advanced-schema');
        $schemaContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{resourceType}}',
            '{{fields}}'
        ], [
            $moduleName,
            $entityName,
            $resourceType,
            $fieldDefinitions
        ], $schemaTemplate);

        $schemaDir = base_path("Modules/{$moduleName}/app/JsonApi/V1/" . Str::plural($entityName));
        if (!File::exists($schemaDir)) {
            File::makeDirectory($schemaDir, 0755, true);
        }
        
        $schemaPath = $schemaDir . '/' . $entityName . 'Schema.php';
        File::put($schemaPath, $schemaContent);
    }

    private function generateSchemaRelationships(string $entityName, array $relationships): string
    {
        $definitions = [];
        
        foreach ($relationships as $rel) {
            if ($rel['entityA'] === $entityName) {
                $relatedEntity = $rel['entityB'];
                $methodName = $this->getRelationshipMethodName($rel['type'], $relatedEntity, false);
                $schemaType = in_array($rel['type'], ['one-to-many', 'many-to-many']) ? 'HasMany' : 'BelongsTo';
                $definitions[] = "            {$schemaType}::make('{$methodName}'),";
            } elseif ($rel['entityB'] === $entityName) {
                $relatedEntity = $rel['entityA'];
                $methodName = $this->getRelationshipMethodName($rel['type'], $relatedEntity, true);
                $schemaType = $rel['type'] === 'one-to-many' ? 'BelongsTo' : 'HasMany';
                $definitions[] = "            {$schemaType}::make('{$methodName}'),";
            }
        }
        
        return implode("\n", $definitions);
    }

    // Stub methods and utility functions...
    private function generateAdvancedFactory($moduleName, $entity) { /* Implement */ }
    
    private function generateAdvancedResource($moduleName, $entity, $relationships) 
    {
        $entityName = $entity['name'];
        $this->info("📄 Generating Resource for {$entityName}...");
        
        $resourceTemplate = $this->getStub('resource');
        $resourceContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{directFields}}',
            '{{relationships}}'
        ], [
            $moduleName,
            $entityName,
            $this->generateResourceFields($entity),
            $this->generateResourceRelationships($entityName, $relationships)
        ], $resourceTemplate);

        $resourceDir = base_path("Modules/{$moduleName}/app/JsonApi/V1/" . Str::plural($entityName));
        $resourcePath = $resourceDir . '/' . $entityName . 'Resource.php';
        
        // Ensure directory exists
        if (!File::isDirectory($resourceDir)) {
            File::makeDirectory($resourceDir, 0755, true);
        }
        
        // Normalize line endings before writing
        $resourceContent = str_replace(["\r\n", "\r"], "\n", $resourceContent);
        File::put($resourcePath, $resourceContent);
    }
    
    private function generateAdvancedAuthorizer($moduleName, $entity) 
    {
        $entityName = $entity['name'];
        $this->info("🔐 Generating Authorizer for {$entityName}...");
        
        $authorizerTemplate = $this->getStub('authorizer');
        
        // Generate permission prefix - use from config if available, otherwise entity name
        if ($this->permissionsConfig && isset($this->permissionsConfig['prefix'])) {
            $permissionPrefix = $this->permissionsConfig['prefix'];
        } else {
            $permissionPrefix = Str::lower(Str::plural($entityName));
        }
        
        $authorizerContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{modelPlural}}',
            '{{permissionPrefix}}'
        ], [
            $moduleName,
            $entityName,
            Str::plural($entityName),
            $permissionPrefix
        ], $authorizerTemplate);

        $authorizerDir = base_path("Modules/{$moduleName}/app/JsonApi/V1/" . Str::plural($entityName));
        $authorizerPath = $authorizerDir . '/' . $entityName . 'Authorizer.php';
        File::put($authorizerPath, $authorizerContent);
    }
    
    private function generateAdvancedRequests($moduleName, $entity) 
    {
        $entityName = $entity['name'];
        $this->info("📝 Generating Request for {$entityName}...");
        
        $requestTemplate = $this->getStub('request');
        
        $rules = $this->generateValidationRules($entity);
        $messages = $this->generateValidationMessages($entity);
        
        $requestContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}',
            '{{modelNameLower}}',
            '{{modelPlural}}',
            '{{rules}}',
            '{{messages}}'
        ], [
            $moduleName,
            $entityName,
            Str::lower($entityName),
            Str::plural($entityName),
            $rules,
            $messages
        ], $requestTemplate);

        $requestDir = base_path("Modules/{$moduleName}/app/JsonApi/V1/" . Str::plural($entityName));
        
        // Ensure directory exists
        if (!File::isDirectory($requestDir)) {
            File::makeDirectory($requestDir, 0755, true);
        }
        
        $requestPath = $requestDir . '/' . $entityName . 'Request.php';
        
        // Normalize line endings before writing
        $requestContent = str_replace(["\r\n", "\r"], "\n", $requestContent);
        File::put($requestPath, $requestContent);
    }
    
    private function generateAdvancedController($moduleName, $entity)
    {
        $entityName = $entity['name'];
        $this->info("🎮 Generating Controller for {$entityName}...");
        
        $controllerTemplate = $this->getStub('controller');
        $controllerContent = str_replace([
            '{{moduleName}}',
            '{{modelName}}'
        ], [
            $moduleName,
            $entityName
        ], $controllerTemplate);

        $controllerDir = base_path("Modules/{$moduleName}/app/Http/Controllers/Api/V1");
        if (!File::exists($controllerDir)) {
            File::makeDirectory($controllerDir, 0755, true);
        }
        
        $controllerPath = $controllerDir . '/' . $entityName . 'Controller.php';
        
        // Normalize line endings before writing
        $controllerContent = str_replace(["\r\n", "\r"], "\n", $controllerContent);
        File::put($controllerPath, $controllerContent);
    }
    
    private function generateAdvancedTests($moduleName, $entity) 
    {
        $entityName = $entity['name'];
        $this->info("🧪 Generating Tests for {$entityName}...");
        
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
                $entityName,
                Str::plural($entityName),
                Str::kebab(Str::plural($entityName))
            ], $testTemplate);

            $testDir = base_path("Modules/{$moduleName}/tests/Feature");
            if (!File::exists($testDir)) {
                File::makeDirectory($testDir, 0755, true);
            }
            
            $testPath = $testDir . '/' . $entityName . $testType . 'Test.php';
            File::put($testPath, $testContent);
        }
    }
    
    private function mapToSchemaType(string $fieldType): string
    {
        $mapping = [
            'string' => 'Str',
            'text' => 'Str',
            'integer' => 'Number',
            'bigInteger' => 'Number',
            'decimal' => 'Number',
            'boolean' => 'Boolean',
            'date' => 'DateTime',
            'datetime' => 'DateTime',
            'timestamp' => 'DateTime',
            'json' => 'ArrayHash'
        ];
        
        return $mapping[$fieldType] ?? 'Str';
    }

    private function generateResourceFields(array $entity): string
    {
        return collect($entity['fields'])->map(function($field) {
            return "            '{$field['name']}' => \$this->{$field['name']},";
        })->join("\n");
    }

    private function generateResourceRelationships(string $entityName, array $relationships): string
    {
        $relationshipLines = [];
        
        foreach ($relationships as $rel) {
            if ($rel['entityA'] === $entityName) {
                $relatedEntity = $rel['entityB'];
                $methodName = $this->getRelationshipMethodName($rel['type'], $relatedEntity, false);
                $relationshipLines[] = "            \$this->relationshipData('{$methodName}'),";
            } elseif ($rel['entityB'] === $entityName) {
                $relatedEntity = $rel['entityA'];
                $methodName = $this->getRelationshipMethodName($rel['type'], $relatedEntity, true);
                $relationshipLines[] = "            \$this->relationshipData('{$methodName}'),";
            }
        }
        
        return implode("\n", $relationshipLines);
    }

    private function generateValidationRules(array $entity): string
    {
        $rules = [];
        
        foreach ($entity['fields'] as $field) {
            $fieldName = $field['name'];
            $fieldRules = [];
            
            // Required/nullable
            if (!$field['nullable']) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }
            
            // Type validation
            switch ($field['type']) {
                case 'string':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
                case 'text':
                    $fieldRules[] = 'string';
                    break;
                case 'integer':
                    $fieldRules[] = 'integer';
                    break;
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'json':
                    $fieldRules[] = 'array';
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                default:
                    $fieldRules[] = 'string';
            }
            
            // Unique validation
            if ($field['unique']) {
                $tableName = $entity['tableName'];
                $modelVarName = Str::lower($entity['name']);
                $fieldRules[] = "Rule::unique('{$tableName}')->ignore(\${$modelVarName}?->id)";
            }
            
            // Special handling for email field
            if ($fieldName === 'email') {
                $fieldRules[] = 'email';
            }
            
            $rulesString = "['" . implode("', '", array_filter($fieldRules, function($rule) {
                return !str_contains($rule, 'Rule::');
            })) . "'";
            
            // Add Rule:: validations separately
            $ruleValidations = array_filter($fieldRules, function($rule) {
                return str_contains($rule, 'Rule::');
            });
            
            if (!empty($ruleValidations)) {
                $rulesString .= ", " . implode(", ", $ruleValidations);
            }
            
            $rulesString .= "]";
            
            $rules[] = "            '{$fieldName}' => {$rulesString},";
        }
        
        return implode("\n", $rules);
    }

    private function generateValidationMessages(array $entity): string
    {
        $messages = [];
        
        foreach ($entity['fields'] as $field) {
            $fieldName = $field['name'];
            $fieldLabel = ucfirst(str_replace('_', ' ', $fieldName));
            
            if (!$field['nullable']) {
                $messages[] = "            '{$fieldName}.required' => 'El campo {$fieldLabel} es obligatorio.',";
            }
            
            switch ($field['type']) {
                case 'string':
                case 'text':
                    $messages[] = "            '{$fieldName}.string' => 'El campo {$fieldLabel} debe ser texto.',";
                    if ($field['type'] === 'string') {
                        $messages[] = "            '{$fieldName}.max' => 'El campo {$fieldLabel} no puede tener más de 255 caracteres.',";
                    }
                    break;
                case 'integer':
                    $messages[] = "            '{$fieldName}.integer' => 'El campo {$fieldLabel} debe ser un número entero.',";
                    break;
                case 'boolean':
                    $messages[] = "            '{$fieldName}.boolean' => 'El campo {$fieldLabel} debe ser verdadero o falso.',";
                    break;
                case 'date':
                    $messages[] = "            '{$fieldName}.date' => 'El campo {$fieldLabel} debe ser una fecha válida.',";
                    break;
                case 'json':
                    $messages[] = "            '{$fieldName}.array' => 'El campo {$fieldLabel} debe ser un arreglo.',";
                    break;
            }
            
            if ($field['unique']) {
                $messages[] = "            '{$fieldName}.unique' => 'Este {$fieldLabel} ya está en uso.',";
            }
            
            if ($fieldName === 'email') {
                $messages[] = "            '{$fieldName}.email' => 'El formato del email no es válido.',";
            }
        }
        
        return implode("\n", $messages);
    }

    private function arrayToString(array $array): string
    {
        if (empty($array)) return '[]';
        
        $items = [];
        foreach ($array as $key => $value) {
            $items[] = "        '{$key}' => '{$value}'";
        }
        
        return "[\n" . implode(",\n", $items) . "\n    ]";
    }

    private function getStub(string $name): string
    {
        $stubPath = base_path("app/Console/Commands/stubs/module-blueprint/{$name}.stub");
        return File::exists($stubPath) ? File::get($stubPath) : $this->getDefaultStub($name);
    }

    private function getDefaultStub(string $name): string
    {
        // Return basic stubs for the advanced features
        switch ($name) {
            case 'advanced-model':
                return '<?php

namespace Modules\{{moduleName}}\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class {{modelName}} extends Model
{
    use HasFactory;

    protected $table = "{{tableName}}";

    protected $fillable = [
        {{fillable}}
    ];

    protected $casts = {{casts}};

{{relationships}}
}';

            case 'advanced-migration':
                return '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("{{tableName}}", function (Blueprint $table) {
            $table->id();
{{fields}}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{{tableName}}");
    }
};';

            case 'pivot-migration':
                return '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("{{tableName}}", function (Blueprint $table) {
            $table->id();
            $table->foreignId("{{foreignKeyA}}")->constrained("{{tableA}}")->onDelete("cascade");
            $table->foreignId("{{foreignKeyB}}")->constrained("{{tableB}}")->onDelete("cascade");
            $table->timestamps();

            $table->unique(["{{foreignKeyA}}", "{{foreignKeyB}}"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{{tableName}}");
    }
};';

            case 'advanced-schema':
                return '<?php

namespace Modules\{{moduleName}}\JsonApi\V1\{{modelName}}s;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\{{moduleName}}\Models\{{modelName}};

class {{modelName}}Schema extends Schema
{
    public static string $model = {{modelName}}::class;

    public function fields(): array
    {
        return [
            ID::make(),
{{fields}}
            DateTime::make("createdAt")->sortable()->readOnly(),
            DateTime::make("updatedAt")->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "{{resourceType}}";
    }
}';

            case 'resource':
                return '<?php

namespace Modules\{{moduleName}}\JsonApi\V1\{{modelName}}s;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class {{modelName}}Resource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
{{directFields}}
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
{{relationships}}
        ];
    }
}';

            // case 'advanced-authorizer': - Now using external stub
            //     return '<?php
            // 
            // namespace Modules\{{moduleName}}\JsonApi\V1\{{modelName}}s;
            // 
            // use LaravelJsonApi\Core\Store\LazyRelation;
            // use LaravelJsonApi\Laravel\Authorizers\ResourceAuthorizer;
            // 
            // class {{modelName}}Authorizer extends ResourceAuthorizer
            // {
            //     /**
            //      * Determine if the user can view any resources.
            //      */
            //     public function index(): bool
            //     {
            //         return true;
            //     }
            // 
            //     /**
            //      * Determine if the user can view the resource.
            //      */
            //     public function show(object $model): bool
            //     {
            //         return true;
            //     }
            // 
            //     /**
            //      * Determine if the user can create resources.
            //      */
            //     public function store(): bool
            //     {
            //         return true;
            //     }
            // 
            //     /**
            //      * Determine if the user can update the resource.
            //      */
            //     public function update(object $model): bool
            //     {
            //         return true;
            //     }
            // 
            //     /**
            //      * Determine if the user can delete the resource.
            //      */
            //     public function destroy(object $model): bool
            //     {
            //         return true;
            //     }
            // }';

            // case 'advanced-request': - Now using external stub
            //     return '<?php
            // 
            // namespace Modules\{{moduleName}}\JsonApi\V1\{{modelName}}s;
            // 
            // use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
            // 
            // class {{modelName}}Request extends ResourceRequest
            // {
            //     /**
            //      * Get the validation rules for the resource.
            //      */
            //     public function rules(): array
            //     {
            //         return [
            //             // Add validation rules here
            //         ];
            //     }
            // }';

            case 'advanced-controller':
                return '<?php

namespace Modules\{{moduleName}}\Http\Controllers;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class {{modelName}}Controller extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;
}';

            default:
                return '';
        }
    }

    private function generateModuleDocumentation(string $moduleName, array $entities, array $relationships)
    {
        $this->info("📚 Generating comprehensive documentation...");
        
        // Create module README with entity relationships
        $readme = "# 📦 {$moduleName} Module\n\n";
        $readme .= "Advanced module with multiple entities and complex relationships.\n\n";
        $readme .= "**Generated:** " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        $readme .= "## 📋 Entities\n\n";
        foreach ($entities as $entity) {
            $readme .= "### {$entity['name']}\n";
            $readme .= "- **Table:** `{$entity['tableName']}`\n";
            $readme .= "- **Fields:** " . count($entity['fields']) . "\n\n";
        }
        
        $readme .= "## 🔗 Relationships\n\n";
        foreach ($relationships as $rel) {
            $readme .= "- **{$rel['entityA']}** ↔ **{$rel['entityB']}** ({$rel['type']})\n";
        }
        
        $readme .= "\n## 🧪 Testing\n\n";
        $readme .= "```bash\n";
        $readme .= "php artisan test Modules/{$moduleName}\n";
        $readme .= "```\n";

        File::put(base_path("Modules/{$moduleName}/README.md"), $readme);
    }

    private function updateComposerJson(string $moduleName)
    {
        $composerPath = base_path("Modules/{$moduleName}/composer.json");
        
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);
            
            // Add JsonApi PSR-4 mapping
            if (isset($composer['autoload']['psr-4'])) {
                $composer['autoload']['psr-4']["Modules\\{$moduleName}\\JsonApi\\"] = "JsonApi/";
            }
            
            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info("📦 Updated composer.json with JsonApi PSR-4 mapping");
        }
    }

    private function showModuleSummary(string $moduleName, array $entities, array $relationships)
    {
        $this->newLine();
        $this->info("📊 Module Summary:");
        $this->line("Module: {$moduleName}");
        $this->line("Entities: " . count($entities));
        $this->line("Relationships: " . count($relationships));
        
        $this->newLine();
        $this->info("🔗 Relationship Details:");
        foreach ($relationships as $rel) {
            $this->line("{$rel['entityA']} ↔ {$rel['entityB']} ({$rel['type']})");
        }
        
        $this->newLine();
        $this->info("✅ Next steps:");
        $this->line("1. Run: php artisan module:seed {$moduleName}");
        $this->line("2. Run: php artisan test Modules/{$moduleName}");
        $this->line("3. Check: /api/v1/{resource-type}");
    }
    
    private function getEntityTableName(string $moduleName, string $entityName): string
    {
        // If we have stored entities data, use the actual table name
        if (isset($this->entities[$entityName]['tableName'])) {
            return $this->entities[$entityName]['tableName'];
        }
        
        // Fallback to Spanish plurals for common cases
        $spanishPluralMap = [
            'Persona' => 'personas',
            'Direccion' => 'direcciones', 
            'DatosPerfil' => 'datos_perfiles'
        ];
        
        return $spanishPluralMap[$entityName] ?? Str::snake(Str::plural($entityName));
    }
}
