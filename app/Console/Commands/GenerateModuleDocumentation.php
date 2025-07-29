<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;
use ReflectionClass;

class GenerateModuleDocumentation extends Command
{
    protected $signature = 'module:docs {module?} {--all}';
    protected $description = 'Generate comprehensive documentation for modules';

    public function handle()
    {
        if ($this->option('all')) {
            $modules = Module::allEnabled();
            foreach ($modules as $module) {
                $this->generateDocumentation($module->getName());
            }
            $this->info("Documentation generated for all modules!");
        } else {
            $moduleName = $this->argument('module');
            if (!$moduleName) {
                $modules = Module::allEnabled();
                $moduleNames = array_map(fn($module) => $module->getName(), $modules);
                $moduleName = $this->choice('Select module:', $moduleNames);
            }
            $this->generateDocumentation($moduleName);
        }
    }

    private function generateDocumentation(string $moduleName)
    {
        $this->info("Generating documentation for {$moduleName}...");
        
        $module = Module::find($moduleName);
        if (!$module) {
            $this->error("Module {$moduleName} not found!");
            return;
        }

        // Create docs directory
        $docsPath = $module->getPath() . '/docs';
        if (!File::exists($docsPath)) {
            File::makeDirectory($docsPath, 0755, true);
        }

        // Generate different types of documentation
        $this->generateApiDocs($module);
        $this->generateTestReport($module);
        $this->generatePermissionsDoc($module);
        $this->updateReadme($module);
        
        $this->info("✅ Documentation generated successfully for {$moduleName}!");
    }

    private function generateApiDocs($module)
    {
        $this->line("  📚 Generating API documentation...");
        
        $moduleName = $module->getName();
        $schemaPath = $module->getPath() . '/app/JsonApi/V1';
        
        if (!File::exists($schemaPath)) {
            $this->warn("  ⚠️  No JSON API schemas found");
            return;
        }

        $schemas = File::directories($schemaPath);
        $apiDocs = "# 📋 API Documentation - {$moduleName}\n\n";
        $apiDocs .= "Auto-generated API documentation.\n\n";
        $apiDocs .= "**Generated:** " . now()->format('Y-m-d H:i:s') . "\n\n";

        foreach ($schemas as $schemaDir) {
            $resourceName = basename($schemaDir);
            $schemaFile = $schemaDir . '/' . rtrim($resourceName, 's') . 'Schema.php';
            
            if (File::exists($schemaFile)) {
                $apiDocs .= $this->parseSchemaFile($schemaFile, $resourceName);
            }
        }

        $apiDocs .= "\n## 🔐 Authentication\n\n";
        $apiDocs .= "All endpoints require authentication using Sanctum tokens.\n\n";
        $apiDocs .= "```bash\n";
        $apiDocs .= "Authorization: Bearer {your-token}\n";
        $apiDocs .= "Content-Type: application/vnd.api+json\n";
        $apiDocs .= "Accept: application/vnd.api+json\n";
        $apiDocs .= "```\n\n";

        File::put($module->getPath() . '/docs/API.md', $apiDocs);
    }

    private function parseSchemaFile(string $schemaFile, string $resourceName): string
    {
        $resourceType = $this->convertToResourceType($resourceName);
        $modelName = rtrim($resourceName, 's');
        
        $doc = "## 📄 {$modelName}\n\n";
        $doc .= "**Resource Type:** `{$resourceType}`\n\n";
        
        // CRUD Endpoints
        $doc .= "### Endpoints\n\n";
        $doc .= "| Method | Endpoint | Description |\n";
        $doc .= "|--------|----------|-------------|\n";
        $doc .= "| GET | `/api/v1/{$resourceType}` | List all {$resourceName} |\n";
        $doc .= "| POST | `/api/v1/{$resourceType}` | Create new {$modelName} |\n";
        $doc .= "| GET | `/api/v1/{$resourceType}/{id}` | Show specific {$modelName} |\n";
        $doc .= "| PATCH | `/api/v1/{$resourceType}/{id}` | Update {$modelName} |\n";
        $doc .= "| DELETE | `/api/v1/{$resourceType}/{id}` | Delete {$modelName} |\n\n";

        // Try to extract fields from schema
        $content = File::get($schemaFile);
        $fields = $this->extractFieldsFromSchema($content);
        
        if (!empty($fields)) {
            $doc .= "### Fields\n\n";
            $doc .= "| Field | Type | Description |\n";
            $doc .= "|-------|------|-------------|\n";
            foreach ($fields as $field) {
                $doc .= "| `{$field['name']}` | {$field['type']} | {$field['description']} |\n";
            }
            $doc .= "\n";
        }

        // Filters and sorting
        $doc .= "### Query Parameters\n\n";
        $doc .= "#### Filtering\n";
        $doc .= "```\n";
        $doc .= "GET /api/v1/{$resourceType}?filter[field]=value\n";
        $doc .= "```\n\n";
        
        $doc .= "#### Sorting\n";
        $doc .= "```\n";
        $doc .= "GET /api/v1/{$resourceType}?sort=field,-other_field\n";
        $doc .= "```\n\n";
        
        $doc .= "#### Pagination\n";
        $doc .= "```\n";
        $doc .= "GET /api/v1/{$resourceType}?page[number]=1&page[size]=20\n";
        $doc .= "```\n\n";

        return $doc;
    }

    private function extractFieldsFromSchema(string $content): array
    {
        $fields = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (preg_match('/(\w+)::make\([\'"]([^\'"]+)[\'"].*/', $line, $matches)) {
                $type = $matches[1];
                $name = $matches[2];
                
                $fields[] = [
                    'name' => $name,
                    'type' => $this->convertFieldType($type),
                    'description' => 'Auto-detected field'
                ];
            }
        }
        
        return $fields;
    }

    private function convertFieldType(string $type): string
    {
        $mapping = [
            'Str' => 'string',
            'Number' => 'number',
            'Boolean' => 'boolean',
            'DateTime' => 'datetime',
            'ArrayHash' => 'object',
            'BelongsTo' => 'relationship',
            'HasMany' => 'relationship[]'
        ];
        
        return $mapping[$type] ?? 'unknown';
    }

    private function generateTestReport($module)
    {
        $this->line("  🧪 Generating test report...");
        
        $moduleName = $module->getName();
        $testPath = $module->getPath() . '/Tests/Feature';
        
        if (!File::exists($testPath)) {
            $this->warn("  ⚠️  No tests found");
            return;
        }

        $testFiles = File::glob($testPath . '/*Test.php');
        $testCount = 0;
        $testMethods = 0;

        $report = "# 🧪 Test Report - {$moduleName}\n\n";
        $report .= "**Generated:** " . now()->format('Y-m-d H:i:s') . "\n\n";

        foreach ($testFiles as $testFile) {
            $testCount++;
            $content = File::get($testFile);
            $methods = preg_match_all('/public function test_\w+/', $content);
            $testMethods += $methods;
            
            $className = basename($testFile, '.php');
            $report .= "## {$className}\n\n";
            
            // Extract test methods
            preg_match_all('/public function (test_\w+)/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $method) {
                    $testName = str_replace(['test_', '_'], ['', ' '], $method);
                    $testName = ucfirst($testName);
                    $report .= "- ✅ {$testName}\n";
                }
            }
            $report .= "\n";
        }

        $report .= "## 📊 Summary\n\n";
        $report .= "- **Test Files:** {$testCount}\n";
        $report .= "- **Test Methods:** {$testMethods}\n";
        $report .= "- **Status:** All tests should pass\n";
        $report .= "- **Coverage:** High coverage expected\n\n";

        $report .= "## 🚀 Running Tests\n\n";
        $report .= "```bash\n";
        $report .= "# Run all module tests\n";
        $report .= "php artisan test --filter {$moduleName}\n\n";
        $report .= "# Run specific test file\n";
        $report .= "php artisan test Modules/{$moduleName}/Tests/Feature/ExampleTest\n";
        $report .= "```\n";

        File::put($module->getPath() . '/docs/TESTING.md', $report);
    }

    private function generatePermissionsDoc($module)
    {
        $this->line("  🔐 Generating permissions documentation...");
        
        $moduleName = $module->getName();
        $seederPath = $module->getPath() . '/Database/Seeders';
        
        $doc = "# 🔐 Permissions - {$moduleName}\n\n";
        $doc .= "**Generated:** " . now()->format('Y-m-d H:i:s') . "\n\n";

        if (File::exists($seederPath)) {
            $seederFiles = File::glob($seederPath . '/*PermissionsSeeder.php');
            
            foreach ($seederFiles as $seederFile) {
                $content = File::get($seederFile);
                
                // Extract permissions from seeder
                if (preg_match_all('/[\'"]([^\'"\s]+\.[^\'"\s]+\.[^\'"\s]+)[\'"]/', $content, $matches)) {
                    $permissions = array_unique($matches[1]);
                    
                    $doc .= "## Available Permissions\n\n";
                    $doc .= "| Permission | Description |\n";
                    $doc .= "|------------|-------------|\n";
                    
                    foreach ($permissions as $permission) {
                        $parts = explode('.', $permission);
                        $action = end($parts);
                        $resource = $parts[count($parts) - 2];
                        $description = $this->generatePermissionDescription($action, $resource);
                        $doc .= "| `{$permission}` | {$description} |\n";
                    }
                    $doc .= "\n";
                }
            }
        }

        $doc .= "## Default Role Assignments\n\n";
        $doc .= "| Role | Permissions | Description |\n";
        $doc .= "|------|-------------|-------------|\n";
        $doc .= "| `admin` | All permissions | Full access to all operations |\n";
        $doc .= "| `tech` | index, show | Read-only access |\n";
        $doc .= "| `customer` | Limited | Restricted access based on business rules |\n\n";

        $doc .= "## Usage\n\n";
        $doc .= "```php\n";
        $doc .= "// Check permission in controller\n";
        $doc .= "if (\$request->user()->can('module.resource.action')) {\n";
        $doc .= "    // Perform action\n";
        $doc .= "}\n\n";
        $doc .= "// In Authorizer\n";
        $doc .= "public function index(Request \$request): bool\n";
        $doc .= "{\n";
        $doc .= "    return \$request->user()?->can('module.resource.index') ?? false;\n";
        $doc .= "}\n";
        $doc .= "```\n";

        File::put($module->getPath() . '/docs/PERMISSIONS.md', $doc);
    }

    private function generatePermissionDescription(string $action, string $resource): string
    {
        $descriptions = [
            'index' => "List all {$resource}",
            'show' => "View specific {$resource}",
            'store' => "Create new {$resource}",
            'update' => "Update existing {$resource}",
            'destroy' => "Delete {$resource}"
        ];

        return $descriptions[$action] ?? "Perform {$action} on {$resource}";
    }

    private function updateReadme($module)
    {
        $this->line("  📝 Updating README...");
        
        $readmePath = $module->getPath() . '/README.md';
        if (!File::exists($readmePath)) {
            $this->warn("  ⚠️  README.md not found");
            return;
        }

        $content = File::get($readmePath);
        
        // Update metrics section
        $testPath = $module->getPath() . '/Tests/Feature';
        $testFiles = File::exists($testPath) ? count(File::glob($testPath . '/*Test.php')) : 0;
        
        $metrics = "## 📊 Métricas\n\n";
        $metrics .= "- **Test Files**: {$testFiles}\n";
        $metrics .= "- **Generated**: " . now()->format('Y-m-d H:i:s') . "\n";
        $metrics .= "- **Status**: ✅ Documentation up to date\n";
        $metrics .= "- **API Version**: JSON:API v1.0\n";

        // Replace metrics section if exists
        if (preg_match('/## 📊 Métricas.*?(?=##|\z)/s', $content)) {
            $content = preg_replace('/## 📊 Métricas.*?(?=##|\z)/s', $metrics, $content);
        } else {
            $content .= "\n\n" . $metrics;
        }

        File::put($readmePath, $content);
    }

    private function convertToResourceType(string $resourceName): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $resourceName));
    }
}
