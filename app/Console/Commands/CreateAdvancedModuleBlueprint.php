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
    private $currentEntityIndex = 0;

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
        // Reset entity index for proper timestamp ordering
        $this->currentEntityIndex = 0;
        
        // Create module structure
        $this->call('module:make', ['name' => [$moduleName]]);
        
        // Update composer.json to include JsonApi PSR-4 mapping
        $this->updateComposerJson($moduleName);
        
        // Sort entities by dependency order to ensure proper migration sequencing
        $sortedEntities = $this->sortEntitiesByDependencies($entities, $relationships);
        
        // Generate each entity in dependency order
        foreach ($sortedEntities as $entity) {
            $this->generateEntityFiles($moduleName, $entity, $relationships);
        }

        // Update main seeder with all entity seeders
        $this->updateMainSeeder($moduleName, $entities);

        // Generate permissions seeder if permissions are configured
        if ($this->permissionsConfig) {
            $this->generatePermissionsSeeder($moduleName, $this->permissionsConfig);
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
        
        // Seeder
        $this->generateAdvancedSeeder($moduleName, $entity);
        
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
            'many-to-one' => 'belongsTo',
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
            
            // Add foreign key constraint for foreignId fields
            if ($field['type'] === 'foreignId' && Str::endsWith($field['name'], '_id')) {
                $relatedTable = $this->inferTableNameFromForeignKey($field['name']);
                if ($relatedTable) {
                    $line .= "->constrained('{$relatedTable}')->onDelete('cascade')";
                }
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

        // Store current entity index for proper timestamp ordering
        $this->currentEntityIndex = ($this->currentEntityIndex ?? 0) + 1;
        
        $timestamp = date('Y_m_d_His', time() + ($this->currentEntityIndex * 5));
        $migrationPath = base_path("Modules/{$moduleName}/Database/Migrations/{$timestamp}_create_{$tableName}_table.php");
        File::put($migrationPath, $migrationContent);
    }

    private function generateForeignKeyFields(string $entityName, array $relationships): string
    {
        $foreignKeys = [];
        
        // Get existing field names to avoid duplicates
        $entity = collect($this->entities)->firstWhere('name', $entityName);
        $existingFields = collect($entity['fields'] ?? [])->pluck('name')->toArray();
        
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
                
                // Check if foreign key field already exists in configuration
                if (!in_array($foreignKeyName, $existingFields)) {
                    $foreignKeys[] = "            \$table->foreignId('{$foreignKeyName}')->constrained('{$relatedTable}')->onDelete('cascade');";
                }
            }
        }
        
        return implode("\n", $foreignKeys);
    }

    private function inferTableNameFromForeignKey($fieldName)
    {
        // Common mappings for foreign keys
        $mappings = [
            'cart_id' => 'shopping_carts',
            'user_id' => 'users',
            'product_id' => 'products',
            'shopping_cart_id' => 'shopping_carts',
            'category_id' => 'categories',
            'brand_id' => 'brands',
            'supplier_id' => 'suppliers',
            'warehouse_id' => 'warehouses',
        ];
        
        if (isset($mappings[$fieldName])) {
            return $mappings[$fieldName];
        }
        
        // Fallback: remove _id and pluralize
        $entityName = Str::replace('_id', '', $fieldName);
        return Str::snake(Str::plural(Str::studly($entityName)));
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
            $camelFieldName = Str::camel($field['name']);
            return "            {$schemaType}::make('{$camelFieldName}'),";
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
    private function generateAdvancedFactory($moduleName, $entity) 
    {
        $entityName = $entity['name'];
        $factoryDir = "Modules/{$moduleName}/Database/factories";
        
        // Create directory if it doesn't exist
        if (!File::exists($factoryDir)) {
            File::makeDirectory($factoryDir, 0755, true);
        }
        
        $factoryName = "{$entityName}Factory";
        $factoryPath = "{$factoryDir}/{$factoryName}.php";
        
        $factoryContent = $this->generateFactoryContent($moduleName, $entity);
        
        File::put($factoryPath, $factoryContent);
    }

    private function generateAdvancedSeeder($moduleName, $entity)
    {
        $entityName = $entity['name'];
        $seederDir = "Modules/{$moduleName}/Database/seeders";
        
        // Create directory if it doesn't exist
        if (!File::exists($seederDir)) {
            File::makeDirectory($seederDir, 0755, true);
        }
        
        $seederName = "{$entityName}Seeder";
        $seederPath = "{$seederDir}/{$seederName}.php";
        
        $seederContent = $this->generateSeederContent($moduleName, $entity);
        
        File::put($seederPath, $seederContent);
    }
    
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
        
        // Generate permission prefix - module.resource format
        if ($this->permissionsConfig && isset($this->permissionsConfig['prefix'])) {
            $modulePrefix = $this->permissionsConfig['prefix']; // e.g., 'ecommerce'
            $resourceName = Str::kebab(Str::plural($entityName)); // e.g., 'coupons'
            $permissionPrefix = $modulePrefix . '.' . $resourceName; // e.g., 'ecommerce.coupons'
        } else {
            $permissionPrefix = Str::lower(Str::plural($entityName)); // fallback
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
        
        // Generate realistic test fields from entity definition
        $testableFields = $this->getTestableFields($entity);
        $factoryFields = $this->getFactoryTestFields($entity);
        $storeTestFields = $this->getStoreTestFields($entity);
        $storeTestDbFields = $this->getStoreTestDbFields($entity);
        $minimalStoreTestFields = $this->getMinimalStoreTestFields($entity);
        
        foreach ($testTypes as $testType) {
            $testTemplate = $this->getStub("test-{$testType}");
            $testContent = str_replace([
                '{{moduleName}}',
                '{{modelName}}',
                '{{modelNameLower}}',
                '{{modelPlural}}',
                '{{resourceType}}',
                '{{testableFields}}',
                '{{factoryFields}}',
                '{{storeTestFields}}',
                '{{storeTestDbFields}}',
                '{{minimalStoreTestFields}}',
                '{{sortableField}}',
                '{{filterableField}}',
                '{{tableName}}'
            ], [
                $moduleName,
                $entityName,
                Str::lcfirst($entityName),
                Str::plural($entityName),
                Str::kebab(Str::plural($entityName)),
                $testableFields,
                $factoryFields,
                $storeTestFields,
                $storeTestDbFields,
                $minimalStoreTestFields,
                $this->getSortableField($entity),
                $this->getFilterableField($entity),
                $entity['tableName'] ?? Str::snake(Str::plural($entityName))
            ], $testTemplate);

            $testDir = base_path("Modules/{$moduleName}/tests/Feature");
            if (!File::exists($testDir)) {
                File::makeDirectory($testDir, 0755, true);
            }
            
            $testPath = $testDir . '/' . $entityName . $testType . 'Test.php';
            File::put($testPath, $testContent);
        }
    }
    
    
    private function getTestableFields($entity): string
    {
        $fields = $entity['fields'] ?? [];
        $testableFields = [];
        
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            
            // Skip fields that shouldn't appear in API responses
            if (in_array($fieldName, ['password', 'remember_token', 'email_verified_at'])) {
                continue;
            }
            
            // Convert snake_case to camelCase for JSON API
            $jsonApiField = Str::camel($fieldName);
            $testableFields[] = "                        '{$jsonApiField}',";
        }
        
        return implode("\n", $testableFields);
    }
    
    private function getFactoryTestFields($entity): string
    {
        $fields = $entity['fields'] ?? [];
        $factoryFields = [];
        
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            
            // Skip foreign keys and auto-generated fields
            if (Str::endsWith($fieldName, '_id') || in_array($fieldName, ['created_at', 'updated_at', 'id'])) {
                continue;
            }
            
            $value = $this->getTestValueForField($fieldName, $fieldType);
            $factoryFields[] = "'{$fieldName}' => {$value}";
        }
        
        return implode(', ', $factoryFields);
    }
    
    private function getTestValueForField($fieldName, $fieldType): string
    {
        // Generate appropriate test values based on field type and name
        switch ($fieldType) {
            case 'string':
                if (Str::contains($fieldName, ['email'])) {
                    return "'test@example.com'";
                } elseif (Str::contains($fieldName, ['name', 'title'])) {
                    return "'Test Name'";
                } elseif (Str::contains($fieldName, ['status'])) {
                    return "'active'";
                } elseif (Str::contains($fieldName, ['code'])) {
                    return "'TEST123'";
                } else {
                    return "'test string'";
                }
            case 'text':
                return "'test description'";
            case 'boolean':
                return 'true';
            case 'integer':
            case 'bigInteger':
                return '100';
            case 'decimal':
                return '99.99';
            case 'date':
            case 'datetime':
                return 'now()';
            default:
                return "'test value'";
        }
    }
    
    private function getSortableField($entity): string
    {
        $fields = $entity['fields'] ?? [];
        
        // Look for common sortable fields
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (in_array($fieldName, ['name', 'title', 'created_at', 'status'])) {
                return Str::camel($fieldName);
            }
        }
        
        // Fallback to first string field
        foreach ($fields as $field) {
            if ($field['type'] === 'string') {
                return Str::camel($field['name']);
            }
        }
        
        return 'createdAt'; // Ultimate fallback
    }
    
    private function getFilterableField($entity): string
    {
        $fields = $entity['fields'] ?? [];
        
        // Look for common filterable fields
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (in_array($fieldName, ['status', 'is_active', 'type', 'category'])) {
                return Str::camel($fieldName);
            }
        }
        
        // Look for boolean fields
        foreach ($fields as $field) {
            if ($field['type'] === 'boolean') {
                return Str::camel($field['name']);
            }
        }
        
        return 'status'; // Ultimate fallback
    }
    
    private function getStoreTestFields($entity): string
    {
        $fields = $entity['fields'] ?? [];
        $testFields = [];
        
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            
            // Skip foreign keys and auto-generated fields
            if (Str::endsWith($fieldName, '_id') || in_array($fieldName, ['created_at', 'updated_at', 'id'])) {
                continue;
            }
            
            $camelFieldName = Str::camel($fieldName);
            $value = $this->getJsonApiTestValueForField($fieldName, $fieldType);
            $testFields[] = "                '{$camelFieldName}' => {$value}";
        }
        
        return implode(",\n", $testFields);
    }
    
    private function getStoreTestDbFields($entity): string
    {
        $fields = $entity['fields'] ?? [];
        $dbFields = [];
        
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            
            // Skip foreign keys and auto-generated fields
            if (Str::endsWith($fieldName, '_id') || in_array($fieldName, ['created_at', 'updated_at', 'id'])) {
                continue;
            }
            
            $value = $this->getDbTestValueForField($fieldName, $fieldType);
            $dbFields[] = "'{$fieldName}' => {$value}";
        }
        
        return implode(', ', $dbFields);
    }
    
    private function getMinimalStoreTestFields($entity): string
    {
        $fields = $entity['fields'] ?? [];
        $requiredFields = [];
        
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            
            // Skip foreign keys and auto-generated fields
            if (Str::endsWith($fieldName, '_id') || in_array($fieldName, ['created_at', 'updated_at', 'id'])) {
                continue;
            }
            
            // Include only typically required fields
            if (in_array($fieldName, ['name', 'title', 'code', 'value', 'used_count']) || 
                Str::contains($fieldName, ['required', 'mandatory']) ||
                $fieldType === 'boolean') {
                
                $camelFieldName = Str::camel($fieldName);
                $value = $this->getJsonApiTestValueForField($fieldName, $fieldType);
                $requiredFields[] = "                '{$camelFieldName}' => {$value}";
            }
        }
        
        return implode(",\n", $requiredFields);
    }
    
    private function getJsonApiTestValueForField($fieldName, $fieldType): string
    {
        // Generate appropriate test values for JSON API requests
        switch ($fieldType) {
            case 'string':
                if (Str::contains($fieldName, ['email'])) {
                    return "'test@example.com'";
                } elseif (Str::contains($fieldName, ['name', 'title'])) {
                    return "'Test Name'";
                } elseif (Str::contains($fieldName, ['status'])) {
                    return "'active'";
                } elseif (Str::contains($fieldName, ['code'])) {
                    return "'TEST123'";
                } else {
                    return "'test string'";
                }
            case 'text':
                return "'test description'";
            case 'boolean':
                return 'true';
            case 'integer':
            case 'bigInteger':
                return '100';
            case 'decimal':
                return '99.99';
            case 'date':
            case 'datetime':
                return "'2024-01-01'"; // Use string format for JSON API
            default:
                return "'test value'";
        }
    }
    
    private function getDbTestValueForField($fieldName, $fieldType): string
    {
        // Generate appropriate test values for database assertions
        switch ($fieldType) {
            case 'string':
                if (Str::contains($fieldName, ['email'])) {
                    return "'test@example.com'";
                } elseif (Str::contains($fieldName, ['name', 'title'])) {
                    return "'Test Name'";
                } elseif (Str::contains($fieldName, ['status'])) {
                    return "'active'";
                } elseif (Str::contains($fieldName, ['code'])) {
                    return "'TEST123'";
                } else {
                    return "'test string'";
                }
            case 'text':
                return "'test description'";
            case 'boolean':
                return 'true';
            case 'integer':
            case 'bigInteger':
                return '100';
            case 'decimal':
                return '99.99';
            default:
                return "'test value'";
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
            $camelCaseField = Str::camel($field['name']); // Convert to camelCase for JSON:API
            return "            '{$camelCaseField}' => \$this->{$field['name']},";
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
use Modules\{{moduleName}}\Database\Factories\{{modelName}}Factory;

class {{modelName}} extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return {{modelName}}Factory::new();
    }

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

            case 'test-Index':
                return '<?php

namespace Modules\{{moduleName}}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{{moduleName}}\Models\{{modelName}};

class {{modelName}}IndexTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where(\'email\', \'admin@example.com\')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where(\'email\', \'tech@example.com\')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where(\'email\', \'customer@example.com\')->firstOrFail();
    }

    public function test_admin_can_list_{{modelPlural}}(): void
    {
        $admin = $this->getAdminUser();
        
        {{modelName}}::factory()->count(3)->create();

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}\');

        $response->assertOk();
        $response->assertJsonStructure([
            \'data\' => [
                \'*\' => [
                    \'id\',
                    \'type\',
                    \'attributes\' => [
{{testableFields}}
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_{{modelPlural}}_by_{{sortableField}}(): void
    {
        $admin = $this->getAdminUser();
        
        {{modelName}}::factory()->create([{{factoryFields}}]);
        {{modelName}}::factory()->create([{{factoryFields}}]);

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}?sort={{sortableField}}\');

        $response->assertOk();
    }

    public function test_admin_can_filter_{{modelPlural}}_by_{{filterableField}}(): void
    {
        $admin = $this->getAdminUser();
        
        {{modelName}}::factory()->create([{{factoryFields}}]);
        {{modelName}}::factory()->create([{{factoryFields}}]);

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}?filter[{{filterableField}}]=test\');

        $response->assertOk();
    }

    public function test_tech_user_can_list_{{modelPlural}}_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}\');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_{{modelPlural}}(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}\');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_{{modelPlural}}(): void
    {
        $response = $this->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}\');

        $response->assertStatus(401);
    }

    public function test_can_paginate_{{modelPlural}}(): void
    {
        $admin = $this->getAdminUser();
        
        {{modelName}}::factory()->count(25)->create();

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}?page[size]=10\');

        $response->assertOk();
        $this->assertCount(10, $response->json(\'data\'));
        $response->assertJsonStructure([\'links\', \'meta\']);
    }
}';

            case 'test-Show':
                return '<?php

namespace Modules\{{moduleName}}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{{moduleName}}\Models\{{modelName}};

class {{modelName}}ShowTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where(\'email\', \'admin@example.com\')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where(\'email\', \'tech@example.com\')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where(\'email\', \'customer@example.com\')->firstOrFail();
    }

    public function test_admin_can_show_{{modelName}}(): void
    {
        $admin = $this->getAdminUser();
        $model = {{modelName}}::factory()->create();

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}/\' . $model->id);

        $response->assertOk();
        $response->assertJsonStructure([
            \'data\' => [
                \'id\',
                \'type\',
                \'attributes\' => [
{{testableFields}}
                ]
            ]
        ]);
    }

    public function test_tech_user_can_show_{{modelName}}_with_permission(): void
    {
        $tech = $this->getTechUser();
        $model = {{modelName}}::factory()->create();

        $response = $this->actingAs($tech, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}/\' . $model->id);

        $response->assertOk();
    }

    public function test_customer_user_cannot_show_{{modelName}}(): void
    {
        $customer = $this->getCustomerUser();
        $model = {{modelName}}::factory()->create();

        $response = $this->actingAs($customer, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}/\' . $model->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_show_{{modelName}}(): void
    {
        $model = {{modelName}}::factory()->create();

        $response = $this->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}/\' . $model->id);

        $response->assertStatus(401);
    }

    public function test_show_non_existent_{{modelName}}_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->get(\'/api/v1/{{resourceType}}/999999\');

        $response->assertStatus(404);
    }
}';

            case 'test-Store':
                return '<?php

namespace Modules\{{moduleName}}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{{moduleName}}\Models\{{modelName}};

class {{modelName}}StoreTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where(\'email\', \'admin@example.com\')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where(\'email\', \'tech@example.com\')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where(\'email\', \'customer@example.com\')->firstOrFail();
    }

    public function test_admin_can_create_{{modelName}}(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            \'type\' => \'{{resourceType}}\',
            \'attributes\' => [
                {{factoryFields}}
            ]
        ];

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->post(\'/api/v1/{{resourceType}}\', compact(\'data\'));

        $response->assertCreated();
        $this->assertDatabaseHas(\'{{tableName}}\', [{{factoryFields}}]);
    }

    public function test_tech_user_can_create_{{modelName}}_with_permission(): void
    {
        $tech = $this->getTechUser();

        $data = [
            \'type\' => \'{{resourceType}}\',
            \'attributes\' => [
                {{factoryFields}}
            ]
        ];

        $response = $this->actingAs($tech, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->post(\'/api/v1/{{resourceType}}\', compact(\'data\'));

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_{{modelName}}(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            \'type\' => \'{{resourceType}}\',
            \'attributes\' => [
                {{factoryFields}}
            ]
        ];

        $response = $this->actingAs($customer, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->post(\'/api/v1/{{resourceType}}\', compact(\'data\'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_{{modelName}}(): void
    {
        $data = [
            \'type\' => \'{{resourceType}}\',
            \'attributes\' => [
                {{factoryFields}}
            ]
        ];

        $response = $this->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->post(\'/api/v1/{{resourceType}}\', compact(\'data\'));

        $response->assertStatus(401);
    }
}';

            case 'test-Update':
                return '<?php

namespace Modules\{{moduleName}}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{{moduleName}}\Models\{{modelName}};

class {{modelName}}UpdateTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where(\'email\', \'admin@example.com\')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where(\'email\', \'tech@example.com\')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where(\'email\', \'customer@example.com\')->firstOrFail();
    }

    public function test_admin_can_update_{{modelName}}(): void
    {
        $admin = $this->getAdminUser();
        $model = {{modelName}}::factory()->create();

        $data = [
            \'type\' => \'{{resourceType}}\',
            \'id\' => $model->id,
            \'attributes\' => [
                {{factoryFields}}
            ]
        ];

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->patch(\'/api/v1/{{resourceType}}/\' . $model->id, compact(\'data\'));

        $response->assertOk();
    }

    public function test_tech_user_can_update_{{modelName}}_with_permission(): void
    {
        $tech = $this->getTechUser();
        $model = {{modelName}}::factory()->create();

        $data = [
            \'type\' => \'{{resourceType}}\',
            \'id\' => $model->id,
            \'attributes\' => [
                {{factoryFields}}
            ]
        ];

        $response = $this->actingAs($tech, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->patch(\'/api/v1/{{resourceType}}/\' . $model->id, compact(\'data\'));

        $response->assertOk();
    }

    public function test_customer_user_cannot_update_{{modelName}}(): void
    {
        $customer = $this->getCustomerUser();
        $model = {{modelName}}::factory()->create();

        $data = [
            \'type\' => \'{{resourceType}}\',
            \'id\' => $model->id,
            \'attributes\' => [
                {{factoryFields}}
            ]
        ];

        $response = $this->actingAs($customer, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->patch(\'/api/v1/{{resourceType}}/\' . $model->id, compact(\'data\'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_{{modelName}}(): void
    {
        $model = {{modelName}}::factory()->create();

        $data = [
            \'type\' => \'{{resourceType}}\',
            \'id\' => $model->id,
            \'attributes\' => [
                {{factoryFields}}
            ]
        ];

        $response = $this->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->patch(\'/api/v1/{{resourceType}}/\' . $model->id, compact(\'data\'));

        $response->assertStatus(401);
    }
}';

            case 'test-Destroy':
                return '<?php

namespace Modules\{{moduleName}}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{{moduleName}}\Models\{{modelName}};

class {{modelName}}DestroyTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where(\'email\', \'admin@example.com\')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where(\'email\', \'tech@example.com\')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where(\'email\', \'customer@example.com\')->firstOrFail();
    }

    public function test_admin_can_delete_{{modelName}}(): void
    {
        $admin = $this->getAdminUser();
        $model = {{modelName}}::factory()->create();

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->delete(\'/api/v1/{{resourceType}}/\' . $model->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing(\'{{tableName}}\', [\'id\' => $model->id]);
    }

    public function test_tech_user_can_delete_{{modelName}}_with_permission(): void
    {
        $tech = $this->getTechUser();
        $model = {{modelName}}::factory()->create();

        $response = $this->actingAs($tech, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->delete(\'/api/v1/{{resourceType}}/\' . $model->id);

        $response->assertStatus(204);
    }

    public function test_customer_user_cannot_delete_{{modelName}}(): void
    {
        $customer = $this->getCustomerUser();
        $model = {{modelName}}::factory()->create();

        $response = $this->actingAs($customer, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->delete(\'/api/v1/{{resourceType}}/\' . $model->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_delete_{{modelName}}(): void
    {
        $model = {{modelName}}::factory()->create();

        $response = $this->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->delete(\'/api/v1/{{resourceType}}/\' . $model->id);

        $response->assertStatus(401);
    }

    public function test_delete_non_existent_{{modelName}}_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, \'sanctum\')
            ->jsonApi()
            ->expects(\'{{resourceType}}\')
            ->delete(\'/api/v1/{{resourceType}}/999999\');

        $response->assertStatus(404);
    }
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

    private function generateFactoryContent($moduleName, $entity)
    {
        $entityName = $entity['name'];
        $namespace = "Modules\\{$moduleName}\\Database\\Factories";
        $modelNamespace = "Modules\\{$moduleName}\\Models\\{$entityName}";
        
        $definition = $this->generateFactoryDefinition($entity);
        $stateMethods = $this->generateFactoryStateMethods($entity);

        return "<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Factories\Factory;
use {$modelNamespace};

class {$entityName}Factory extends Factory
{
    protected \$model = {$entityName}::class;

    public function definition(): array
    {
        return [
{$definition}
        ];
    }
{$stateMethods}
}
";
    }

    private function generateFactoryDefinition($entity)
    {
        $fields = $entity['fields'] ?? [];
        $definitions = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            $fakerMethod = $this->getFakerMethodForField($name, $type);
            $definitions[] = "            '{$name}' => {$fakerMethod},";
        }

        return implode("\n", $definitions);
    }

    private function getFakerMethodForField($name, $type)
    {
        // Email fields
        if (Str::contains($name, 'email')) {
            return "\$this->faker->unique()->safeEmail";
        }
        
        // Session ID - generate string instead of trying to use Session model
        if ($name === 'session_id') {
            return "\$this->faker->optional(0.3)->regexify('sess_[a-f0-9]{32}')";
        }
        
        // ID fields (foreign keys)
        if (Str::endsWith($name, '_id') && $name !== 'id' && $name !== 'session_id') {
            return $this->getFactoryForRelatedModel($name);
        }
        
        // Status fields
        if (Str::contains($name, 'status')) {
            return "\$this->faker->randomElement(['active', 'inactive', 'pending'])";
        }
        
        // Money/price fields
        if (Str::contains($name, ['amount', 'price', 'total', 'cost', 'discount'])) {
            return "\$this->faker->randomFloat(2, 1, 1000)";
        }
        
        // Percentage fields
        if (Str::contains($name, ['percent', 'rate']) && !Str::contains($name, 'amount')) {
            return "\$this->faker->randomFloat(2, 0, 100)";
        }
        
        // Quantity fields
        if (Str::contains($name, ['quantity', 'count', 'qty'])) {
            return "\$this->faker->numberBetween(1, 10)";
        }
        
        // Code fields
        if (Str::contains($name, 'code')) {
            return "\$this->faker->lexify('????##')";
        }
        
        // Currency fields
        if ($name === 'currency') {
            return "\$this->faker->randomElement(['USD', 'EUR', 'MXN'])";
        }
        
        // Date fields
        if (Str::contains($name, ['date', 'at']) || $type === 'datetime' || $type === 'timestamp') {
            if (Str::contains($name, ['expire', 'valid_until'])) {
                return "\$this->faker->dateTimeBetween('+1 day', '+90 days')";
            }
            if (Str::contains($name, ['valid_from', 'start'])) {
                return "\$this->faker->dateTimeBetween('-30 days', 'now')";
            }
            return "\$this->faker->dateTimeBetween('-1 year', '+1 year')";
        }
        
        // Boolean fields
        if ($type === 'boolean' || Str::startsWith($name, 'is_') || Str::startsWith($name, 'has_')) {
            return "\$this->faker->boolean(70)"; // 70% true
        }
        
        // Text fields
        if ($type === 'text' || Str::contains($name, ['description', 'notes', 'comment'])) {
            return "\$this->faker->optional(0.7)->paragraph()";
        }
        
        // Based on type
        switch ($type) {
            case 'string':
                if (Str::contains($name, 'name')) {
                    return "\$this->faker->words(2, true)";
                }
                return "\$this->faker->sentence(3)";
            case 'integer':
                return "\$this->faker->numberBetween(1, 100)";
            case 'decimal':
                return "\$this->faker->randomFloat(2, 1, 100)";
            default:
                return "\$this->faker->sentence()";
        }
    }

    private function generateFactoryStateMethods($entity)
    {
        $entityName = $entity['name'];
        $methods = [];
        
        // Active state for entities with status
        $hasStatus = collect($entity['fields'] ?? [])->contains('name', 'status');
        if ($hasStatus) {
            $methods[] = "
    /**
     * Active {$entityName}
     */
    public function active(): static
    {
        return \$this->state(fn (array \$attributes) => [
            'status' => 'active',
        ]);
    }";
        }
        
        // Inactive state
        if ($hasStatus) {
            $methods[] = "
    /**
     * Inactive {$entityName}
     */
    public function inactive(): static
    {
        return \$this->state(fn (array \$attributes) => [
            'status' => 'inactive',
        ]);
    }";
        }
        
        return implode("\n", $methods);
    }

    private function generateSeederContent($moduleName, $entity)
    {
        $entityName = $entity['name'];
        $namespace = "Modules\\{$moduleName}\\Database\\Seeders";
        $modelNamespace = "Modules\\{$moduleName}\\Models\\{$entityName}";
        
        // Detect external dependencies to add imports
        $externalDependencies = $this->detectExternalDependencies($entity);
        $additionalImports = "";
        
        foreach ($externalDependencies as $fieldName => $modelInfo) {
            $additionalImports .= "use Modules\\{$modelInfo['module']}\\Models\\{$modelInfo['model']};\n";
        }
        
        $seederLogic = $this->generateSeederLogic($entity);

        return "<?php

namespace {$namespace};

use Illuminate\\Database\\Seeder;
use {$modelNamespace};
{$additionalImports}
class {$entityName}Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$this->command->info('🌱 Seeding {$entityName}...');
        
{$seederLogic}
        
        \$this->command->info('✅ {$entityName} seeded successfully!');
    }
}
";
    }

    private function generateSeederLogic($entity)
    {
        $entityName = $entity['name'];
        $hasStatus = collect($entity['fields'] ?? [])->contains('name', 'status');
        
        // Detect external dependencies (foreign keys to other modules)
        $externalDependencies = $this->detectExternalDependencies($entity);
        
        $logic = "";
        
        // Add imports for external models if needed
        if (!empty($externalDependencies)) {
            $logic .= $this->generateExternalModelChecks($externalDependencies);
        }
        
        $logic .= "        // Create sample {$entityName} records\n";
        
        if (!empty($externalDependencies)) {
            // Use intelligent seeding with existing data
            $logic .= $this->generateIntelligentSeederLogic($entity, $externalDependencies);
        } else {
            // Use factory for entities without external dependencies
            $logic .= "        {$entityName}::factory()->count(10)->create();\n";
        }
        
        if ($hasStatus) {
            $logic .= "\n        // Create some active records\n";
            $logic .= "        {$entityName}::factory()->active()->count(5)->create();\n";
            
            $logic .= "\n        // Create some inactive records\n";
            $logic .= "        {$entityName}::factory()->inactive()->count(2)->create();\n";
        }
        
        return $logic;
    }

    private function detectExternalDependencies($entity)
    {
        $dependencies = [];
        $fields = $entity['fields'] ?? [];
        
        // Known external models mapping
        $externalModels = [
            'user_id' => ['model' => 'User', 'module' => 'User', 'table' => 'users'],
            'product_id' => ['model' => 'Product', 'module' => 'Product', 'table' => 'products'],
            'category_id' => ['model' => 'Category', 'module' => 'Product', 'table' => 'categories'],
            'brand_id' => ['model' => 'Brand', 'module' => 'Product', 'table' => 'brands'],
            'supplier_id' => ['model' => 'Supplier', 'module' => 'Purchase', 'table' => 'suppliers'],
            'warehouse_id' => ['model' => 'Warehouse', 'module' => 'Inventory', 'table' => 'warehouses'],
            'customer_id' => ['model' => 'Customer', 'module' => 'Sales', 'table' => 'customers'],
        ];
        
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (isset($externalModels[$fieldName])) {
                $dependencies[$fieldName] = $externalModels[$fieldName];
            }
        }
        
        return $dependencies;
    }

    private function generateExternalModelChecks($dependencies)
    {
        $logic = "";
        
        foreach ($dependencies as $fieldName => $modelInfo) {
            $modelClass = "\\Modules\\{$modelInfo['module']}\\Models\\{$modelInfo['model']}";
            $variableName = Str::camel(Str::plural($modelInfo['model']));
            
            $logic .= "        // Get existing {$modelInfo['model']} records\n";
            $logic .= "        \${$variableName} = {$modelClass}::all();\n";
            $logic .= "        \n";
            $logic .= "        if (\${$variableName}->isEmpty()) {\n";
            $logic .= "            \$this->command->warn('No {$modelInfo['model']} records found. Skipping {$fieldName} seeding.');\n";
            $logic .= "            return;\n";
            $logic .= "        }\n\n";
        }
        
        return $logic;
    }

    private function generateIntelligentSeederLogic($entity, $dependencies)
    {
        $entityName = $entity['name'];
        $logic = "";
        
        // Check if this entity has relationships with external models
        if (count($dependencies) === 1) {
            // Single dependency - create multiple records per parent
            $dependency = array_values($dependencies)[0];
            $variableName = Str::camel(Str::plural($dependency['model']));
            $fieldName = array_keys($dependencies)[0];
            
            $logic .= "        // Create {$entityName} records using existing {$dependency['model']} records\n";
            $logic .= "        \${$variableName}->take(5)->each(function (\$parent) {\n";
            $logic .= "            {$entityName}::factory()\n";
            $logic .= "                ->count(rand(1, 3))\n";
            $logic .= "                ->create(['{$fieldName}' => \$parent->id]);\n";
            $logic .= "        });\n";
            
        } else if (count($dependencies) > 1) {
            // Multiple dependencies - use random combinations
            $logic .= "        // Create {$entityName} records using combinations of existing data\n";
            $logic .= "        for (\$i = 0; \$i < 10; \$i++) {\n";
            $logic .= "            {$entityName}::factory()->create([\n";
            
            foreach ($dependencies as $fieldName => $modelInfo) {
                $variableName = Str::camel(Str::plural($modelInfo['model']));
                $logic .= "                '{$fieldName}' => \${$variableName}->random()->id,\n";
            }
            
            $logic .= "            ]);\n";
            $logic .= "        }\n";
        }
        
        return $logic;
    }

    private function updateMainSeeder($moduleName, $entities)
    {
        $seederPath = "Modules/{$moduleName}/Database/seeders/{$moduleName}DatabaseSeeder.php";
        
        if (!File::exists($seederPath)) {
            $this->warn("⚠️ Main seeder not found at: {$seederPath}");
            return;
        }
        
        // Generate seeder calls
        $seederCalls = collect($entities)->map(function($entity) {
            return "            {$entity['name']}Seeder::class,";
        })->implode("\n");
        
        // Generate seeder content
        $seederContent = "<?php

namespace Modules\\{$moduleName}\\Database\\Seeders;

use Illuminate\\Database\\Seeder;

class {$moduleName}DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$this->command->info('🏪 Seeding {$moduleName} module...');
        
        \$this->call([
{$seederCalls}
        ]);
        
        \$this->command->info('🎉 {$moduleName} module seeded successfully!');
    }
}
";
        
        File::put($seederPath, $seederContent);
        $this->info("📝 Updated main seeder with all entity seeders");
    }

    private function getFactoryForRelatedModel($fieldName)
    {
        $relatedModel = Str::studly(Str::replace('_id', '', $fieldName));
        
        // Map known model relationships to their correct modules
        $modelModuleMap = [
            'User' => 'User',
            'Product' => 'Product', 
            'Category' => 'Product',
            'Brand' => 'Product',
            'Unit' => 'Product',
            'Inventory' => 'Inventory',
            'Warehouse' => 'Inventory',
            'Purchase' => 'Purchase',
            'Supplier' => 'Purchase',
            'Sale' => 'Sales',
            'Customer' => 'Sales',
            'ShoppingCart' => 'Ecommerce',
            'Cart' => 'Ecommerce',
            'CartItem' => 'Ecommerce',
            'Coupon' => 'Ecommerce',
        ];
        
        // Define external models that should use existing records, not factories
        $externalModels = [
            'user_id' => 'User',
            'product_id' => 'Product',
            'category_id' => 'Category',
            'brand_id' => 'Brand',
            'unit_id' => 'Unit',
            'warehouse_id' => 'Warehouse',
            'supplier_id' => 'Supplier',
            'customer_id' => 'Customer',
        ];
        
        // For external dependencies, generate a placeholder that uses existing records
        if (isset($externalModels[$fieldName])) {
            $modelName = $externalModels[$fieldName];
            $moduleName = $modelModuleMap[$modelName] ?? 'Core';
            return "1, // TODO: Use existing {$modelName} ID - \\Modules\\{$moduleName}\\Models\\{$modelName}::inRandomOrder()->first()?->id ?? 1";
        }
        
        // Special handling for internal module relationships
        if ($fieldName === 'cart_id' || $fieldName === 'shopping_cart_id') {
            return "\\Modules\\Ecommerce\\Models\\ShoppingCart::factory()";
        }
        
        // Try to map to internal module models
        if (isset($modelModuleMap[$relatedModel])) {
            $moduleName = $modelModuleMap[$relatedModel];
            // Only use factory for models within the same module being generated
            if ($this->isInternalModuleModel($relatedModel, $moduleName)) {
                return "\\Modules\\{$moduleName}\\Models\\{$relatedModel}::factory()";
            }
        }
        
        // Fallback to a simple integer for unknown relationships
        return "\$this->faker->numberBetween(1, 100)";
    }
    
    private function isInternalModuleModel($modelName, $moduleName)
    {
        // This should check if the model belongs to the module being generated
        // For now, we'll use a simple heuristic
        $internalModels = ['ShoppingCart', 'CartItem', 'Coupon']; // These are internal to Ecommerce
        
        return in_array($modelName, $internalModels);
    }

    private function generatePermissionsSeeder($moduleName, $permissionsConfig)
    {
        $seederDir = "Modules/{$moduleName}/Database/seeders";
        $seederPath = "{$seederDir}/PermissionsSeeder.php";
        
        $seederContent = $this->generatePermissionsSeederContent($moduleName, $permissionsConfig);
        
        File::put($seederPath, $seederContent);
        $this->info("🔐 Generated permissions seeder");

        // Also update the main seeder to include the permissions seeder
        $this->updateMainSeederWithPermissions($moduleName);
    }

    private function generatePermissionsSeederContent($moduleName, $permissionsConfig)
    {
        $namespace = "Modules\\{$moduleName}\\Database\\Seeders";
        $prefix = $permissionsConfig['prefix'] ?? strtolower($moduleName);
        $resources = $permissionsConfig['resources'] ?? [];
        $actions = $permissionsConfig['actions'] ?? ['index', 'show', 'store', 'update', 'destroy'];
        $roles = $permissionsConfig['roles'] ?? [];

        // Generate permissions creation code
        $permissionsCode = $this->generatePermissionsCreationCode($prefix, $resources, $actions);
        
        // Generate role assignments code
        $roleAssignmentsCode = $this->generateRoleAssignmentsCode($prefix, $resources, $actions, $roles);

        return "<?php

namespace {$namespace};

use Illuminate\\Database\\Seeder;
use Spatie\\Permission\\Models\\Permission;
use Spatie\\Permission\\Models\\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$this->command->info('🔐 Seeding {$moduleName} permissions...');
        
        // Create permissions
{$permissionsCode}
        
        // Assign permissions to roles
{$roleAssignmentsCode}
        
        \$this->command->info('✅ {$moduleName} permissions seeded successfully!');
    }
}
";
    }

    private function generatePermissionsCreationCode($prefix, $resources, $actions)
    {
        $permissions = [];
        
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionName = "{$prefix}.{$resource}.{$action}";
                $permissions[] = "        Permission::firstOrCreate([
            'name' => '{$permissionName}',
            'guard_name' => 'api',
        ]);";
            }
        }

        return implode("\n", $permissions);
    }

    private function generateRoleAssignmentsCode($prefix, $resources, $actions, $roles)
    {
        $assignments = [];
        
        foreach ($roles as $roleName => $rolePermissions) {
            $assignments[] = "\n        // {$roleName} role permissions";
            $assignments[] = "        \$role{$roleName} = Role::where('name', '{$roleName}')->where('guard_name', 'api')->first();";
            $assignments[] = "        if (\$role{$roleName}) {";
            
            foreach ($rolePermissions as $permission) {
                if ($permission === 'all') {
                    // Grant all permissions for this module
                    foreach ($resources as $resource) {
                        foreach ($actions as $action) {
                            $permissionName = "{$prefix}.{$resource}.{$action}";
                            $assignments[] = "            \$role{$roleName}->givePermissionTo('{$permissionName}');";
                        }
                    }
                } elseif (str_contains($permission, '.*')) {
                    // Grant all actions for specific resource
                    $resource = str_replace('.*', '', $permission);
                    foreach ($actions as $action) {
                        $permissionName = "{$prefix}.{$resource}.{$action}";
                        $assignments[] = "            \$role{$roleName}->givePermissionTo('{$permissionName}');";
                    }
                } else {
                    // Grant specific permission
                    $permissionName = "{$prefix}.{$permission}";
                    $assignments[] = "            \$role{$roleName}->givePermissionTo('{$permissionName}');";
                }
            }
            
            $assignments[] = "        }";
        }

        return implode("\n", $assignments);
    }

    private function updateMainSeederWithPermissions($moduleName)
    {
        $seederPath = "Modules/{$moduleName}/Database/seeders/{$moduleName}DatabaseSeeder.php";
        
        if (!File::exists($seederPath)) {
            return;
        }

        $content = File::get($seederPath);
        
        // Add PermissionsSeeder to the calls array
        $content = str_replace(
            "        \$this->call([\n",
            "        \$this->call([\n            PermissionsSeeder::class,\n",
            $content
        );

        File::put($seederPath, $content);
        $this->info("📝 Added permissions seeder to main seeder");
    }

    private function sortEntitiesByDependencies(array $entities, array $relationships): array
    {
        $sorted = [];
        $remaining = $entities;
        $maxIterations = count($entities) * 2; // Prevent infinite loops
        $iterations = 0;
        
        while (!empty($remaining) && $iterations < $maxIterations) {
            $iterations++;
            $addedInThisIteration = false;
            
            foreach ($remaining as $index => $entity) {
                if ($this->canAddEntity($entity, $sorted, $relationships)) {
                    $sorted[] = $entity;
                    unset($remaining[$index]);
                    $remaining = array_values($remaining); // Re-index array
                    $addedInThisIteration = true;
                    break; // Start over to maintain proper order
                }
            }
            
            // If we couldn't add any entity in this iteration, add the first remaining one
            if (!$addedInThisIteration && !empty($remaining)) {
                $sorted[] = array_shift($remaining);
            }
        }
        
        // Add any remaining entities (shouldn't happen with proper logic)
        return array_merge($sorted, $remaining);
    }
    
    private function canAddEntity(array $entity, array $alreadySorted, array $relationships): bool
    {
        $entityName = $entity['name'];
        $entityFields = $entity['fields'] ?? [];
        
        // Check if this entity has foreign key dependencies
        foreach ($entityFields as $field) {
            $fieldName = $field['name'];
            
            // Skip non-foreign key fields
            if (!Str::endsWith($fieldName, '_id') || $fieldName === 'id') {
                continue;
            }
            
            // Check if this foreign key references an entity in our module
            $referencedEntity = $this->getReferencedEntityFromForeignKey($fieldName, $relationships);
            
            if ($referencedEntity && !$this->isEntityInSorted($referencedEntity, $alreadySorted)) {
                return false; // Can't add this entity yet
            }
        }
        
        return true; // No unresolved dependencies
    }
    
    private function getReferencedEntityFromForeignKey(string $fieldName, array $relationships): ?string
    {
        // For relationships defined in config
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'many-to-one' && isset($rel['fields']) && 
                in_array($fieldName, $rel['fields'])) {
                return $rel['entityB'] ?? null;
            }
            if ($rel['type'] === 'one-to-many' && isset($rel['foreignKey']) && 
                $rel['foreignKey'] === $fieldName) {
                return $rel['entityA'] ?? null;
            }
        }
        
        // For internal module references we can detect
        $internalReferences = [
            'shopping_cart_id' => 'ShoppingCart',
            'cart_id' => 'ShoppingCart',
            'cart_item_id' => 'CartItem',
            'coupon_id' => 'Coupon',
        ];
        
        return $internalReferences[$fieldName] ?? null;
    }
    
    private function isEntityInSorted(string $entityName, array $sortedEntities): bool
    {
        foreach ($sortedEntities as $entity) {
            if ($entity['name'] === $entityName) {
                return true;
            }
        }
        return false;
    }
}
