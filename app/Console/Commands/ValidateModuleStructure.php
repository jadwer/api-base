<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class ValidateModuleStructure extends Command
{
    protected $signature = 'module:validate {module?} {--fix} {--all}';
    protected $description = 'Validate module structure and standards compliance';

    private array $errors = [];
    private array $warnings = [];
    private array $suggestions = [];
    private int $totalFiles = 0;
    private int $validatedFiles = 0;

    public function handle()
    {
        $this->info("🔍 Module Structure Validator");
        $this->line("============================");

        if ($this->option('all')) {
            $modules = Module::allEnabled();
            foreach ($modules as $module) {
                $this->validateModule($module->getName());
                $this->line("");
            }
            $this->displaySummary();
        } else {
            $moduleName = $this->argument('module');
            if (!$moduleName) {
                $modules = Module::allEnabled();
                $moduleNames = array_map(fn($module) => $module->getName(), $modules);
                $moduleName = $this->choice('Select module:', $moduleNames);
            }
            $this->validateModule($moduleName);
            $this->displaySummary();
        }
    }

    private function validateModule(string $moduleName): void
    {
        $this->info("📦 Validating: {$moduleName}");
        
        $module = Module::find($moduleName);
        if (!$module) {
            $this->error("❌ Module {$moduleName} not found!");
            return;
        }

        $modulePath = $module->getPath();
        $this->line("   Path: {$modulePath}");

        // Reset counters for this module
        $moduleErrors = count($this->errors);
        $moduleWarnings = count($this->warnings);

        // Validate structure
        $this->validateCoreStructure($modulePath, $moduleName);
        $this->validateJsonApiStructure($modulePath, $moduleName);
        $this->validateTestStructure($modulePath, $moduleName);
        $this->validateDocumentation($modulePath, $moduleName);
        $this->validatePermissions($modulePath, $moduleName);
        $this->validateNamingConventions($modulePath, $moduleName);

        // Summary for this module
        $newErrors = count($this->errors) - $moduleErrors;
        $newWarnings = count($this->warnings) - $moduleWarnings;

        if ($newErrors === 0 && $newWarnings === 0) {
            $this->info("   ✅ Module structure is perfect!");
        } else {
            $this->warn("   ⚠️  Found {$newErrors} errors and {$newWarnings} warnings");
        }
    }

    private function validateCoreStructure(string $modulePath, string $moduleName): void
    {
        $this->line("   🏗️  Checking core structure...");

        $requiredDirs = [
            'app/Http/Controllers',
            'app/Models',
            'app/JsonApi/V1',
            'Database/Seeders',
            'Database/Migrations',
            'Database/Factories',
            'Tests/Feature',
            'Tests/Unit'
        ];

        foreach ($requiredDirs as $dir) {
            $fullPath = $modulePath . '/' . $dir;
            if (!File::exists($fullPath)) {
                $this->addError("Missing required directory: {$dir}");
                if ($this->option('fix')) {
                    File::makeDirectory($fullPath, 0755, true);
                    $this->info("      🔧 Created: {$dir}");
                }
            } else {
                $this->validatedFiles++;
            }
            $this->totalFiles++;
        }

        // Check for composer.json
        if (!File::exists($modulePath . '/composer.json')) {
            $this->addError("Missing composer.json file");
        } else {
            $this->validatedFiles++;
        }
        $this->totalFiles++;

        // Check module.json
        if (!File::exists($modulePath . '/module.json')) {
            $this->addError("Missing module.json file");
        } else {
            $this->validateModuleJson($modulePath . '/module.json', $moduleName);
            $this->validatedFiles++;
        }
        $this->totalFiles++;
    }

    private function validateJsonApiStructure(string $modulePath, string $moduleName): void
    {
        $this->line("   🔗 Checking JSON:API structure...");

        $jsonApiPath = $modulePath . '/app/JsonApi/V1';
        if (!File::exists($jsonApiPath)) {
            $this->addError("Missing JSON:API V1 directory");
            return;
        }

        $schemas = File::directories($jsonApiPath);
        foreach ($schemas as $schemaPath) {
            $resourceName = basename($schemaPath);
            $this->validateJsonApiResource($schemaPath, $resourceName, $moduleName);
        }
    }

    private function validateJsonApiResource(string $schemaPath, string $resourceName, string $moduleName): void
    {
        $singularName = rtrim($resourceName, 's');
        $requiredFiles = [
            $singularName . 'Schema.php',
            $singularName . 'Resource.php',
            $singularName . 'Authorizer.php',
            $singularName . 'Request.php'
        ];

        foreach ($requiredFiles as $file) {
            if (!File::exists($schemaPath . '/' . $file)) {
                $this->addError("Missing JSON:API file: {$resourceName}/{$file}");
            } else {
                $this->validateJsonApiFile($schemaPath . '/' . $file, $moduleName);
                $this->validatedFiles++;
            }
            $this->totalFiles++;
        }
    }

    private function validateJsonApiFile(string $filePath, string $moduleName): void
    {
        $content = File::get($filePath);
        $fileName = basename($filePath);

        // Check namespace
        $expectedNamespace = "Modules\\{$moduleName}\\app\\JsonApi\\V1";
        if (!str_contains($content, $expectedNamespace)) {
            $this->addWarning("Incorrect namespace in {$fileName}");
        }

        // Check specific patterns based on file type
        if (str_contains($fileName, 'Authorizer')) {
            $this->validateAuthorizerFile($content, $fileName);
        } elseif (str_contains($fileName, 'Schema')) {
            $this->validateSchemaFile($content, $fileName);
        } elseif (str_contains($fileName, 'Resource')) {
            $this->validateResourceFile($content, $fileName);
        }
    }

    private function validateAuthorizerFile(string $content, string $fileName): void
    {
        $requiredMethods = ['index', 'show', 'store', 'update', 'destroy'];
        
        foreach ($requiredMethods as $method) {
            if (!str_contains($content, "public function {$method}(")) {
                $this->addError("Missing {$method} method in {$fileName}");
            }
        }

        // Check for permission-based authorization
        if (!str_contains($content, '->can(')) {
            $this->addWarning("Authorizer should use permission-based authorization in {$fileName}");
        }
    }

    private function validateSchemaFile(string $content, string $fileName): void
    {
        if (!str_contains($content, 'public function fields()')) {
            $this->addError("Missing fields() method in {$fileName}");
        }

        if (!str_contains($content, 'public function filters()')) {
            $this->addWarning("Consider adding filters() method in {$fileName}");
        }
    }

    private function validateResourceFile(string $content, string $fileName): void
    {
        if (!str_contains($content, 'public function attributes(')) {
            $this->addError("Missing attributes() method in {$fileName}");
        }

        // Check for hybrid approach (fields + relationships)
        if (str_contains($content, 'BelongsTo') || str_contains($content, 'HasMany')) {
            if (!str_contains($content, '_id')) {
                $this->addSuggestion("Consider hybrid approach (direct field + relationship) in {$fileName}");
            }
        }
    }

    private function validateTestStructure(string $modulePath, string $moduleName): void
    {
        $this->line("   🧪 Checking test structure...");

        $testPath = $modulePath . '/Tests/Feature';
        if (!File::exists($testPath)) {
            $this->addError("Missing Tests/Feature directory");
            return;
        }

        $testFiles = File::glob($testPath . '/*Test.php');
        if (empty($testFiles)) {
            $this->addWarning("No test files found in Tests/Feature");
            return;
        }

        foreach ($testFiles as $testFile) {
            $this->validateTestFile($testFile, $moduleName);
            $this->validatedFiles++;
            $this->totalFiles++;
        }
    }

    private function validateTestFile(string $testFile, string $moduleName): void
    {
        $content = File::get($testFile);
        $fileName = basename($testFile);

        // Check for required test methods
        $crudMethods = ['test_index', 'test_show', 'test_store', 'test_update', 'test_destroy'];
        $foundMethods = 0;

        foreach ($crudMethods as $method) {
            if (str_contains($content, $method)) {
                $foundMethods++;
            }
        }

        if ($foundMethods < 3) {
            $this->addWarning("Test file {$fileName} has only {$foundMethods} CRUD tests");
        }

        // Check for proper test structure
        if (!str_contains($content, 'use RefreshDatabase')) {
            $this->addWarning("Test {$fileName} should use RefreshDatabase trait");
        }

        if (!str_contains($content, 'assertJsonApiResponse')) {
            $this->addSuggestion("Consider using assertJsonApiResponse helper in {$fileName}");
        }
    }

    private function validateDocumentation(string $modulePath, string $moduleName): void
    {
        $this->line("   📚 Checking documentation...");

        $requiredDocs = [
            'README.md',
            'CHANGELOG.md'
        ];

        foreach ($requiredDocs as $doc) {
            if (!File::exists($modulePath . '/' . $doc)) {
                $this->addWarning("Missing documentation: {$doc}");
                if ($this->option('fix')) {
                    $this->createBasicDoc($modulePath . '/' . $doc, $moduleName);
                    $this->info("      🔧 Created: {$doc}");
                }
            } else {
                $this->validatedFiles++;
            }
            $this->totalFiles++;
        }

        // Check for docs directory
        if (!File::exists($modulePath . '/docs')) {
            $this->addSuggestion("Consider creating docs/ directory for additional documentation");
        }
    }

    private function validatePermissions(string $modulePath, string $moduleName): void
    {
        $this->line("   🔐 Checking permissions...");

        $seederPath = $modulePath . '/Database/Seeders';
        $permissionSeeder = null;

        if (File::exists($seederPath)) {
            $seeders = File::glob($seederPath . '/*PermissionsSeeder.php');
            if (empty($seeders)) {
                $this->addWarning("No permissions seeder found");
            } else {
                foreach ($seeders as $seeder) {
                    $this->validatePermissionsSeeder($seeder);
                    $this->validatedFiles++;
                }
            }
            $this->totalFiles++;
        }
    }

    private function validatePermissionsSeeder(string $seederPath): void
    {
        $content = File::get($seederPath);
        $fileName = basename($seederPath);

        $actions = ['index', 'show', 'store', 'update', 'destroy'];
        $foundActions = 0;

        foreach ($actions as $action) {
            if (str_contains($content, ".{$action}")) {
                $foundActions++;
            }
        }

        if ($foundActions < 3) {
            $this->addWarning("Permissions seeder {$fileName} has only {$foundActions} CRUD permissions");
        }
    }

    private function validateNamingConventions(string $modulePath, string $moduleName): void
    {
        $this->line("   📝 Checking naming conventions...");

        // Check if module name follows PascalCase
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $moduleName)) {
            $this->addWarning("Module name should follow PascalCase convention");
        }

        // Check model files
        $modelsPath = $modulePath . '/app/Models';
        if (File::exists($modelsPath)) {
            $models = File::glob($modelsPath . '/*.php');
            foreach ($models as $model) {
                $modelName = basename($model, '.php');
                if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $modelName)) {
                    $this->addWarning("Model {$modelName} should follow PascalCase convention");
                }
                $this->validatedFiles++;
                $this->totalFiles++;
            }
        }
    }

    private function validateModuleJson(string $jsonPath, string $moduleName): void
    {
        $content = File::get($jsonPath);
        $data = json_decode($content, true);

        if (!$data) {
            $this->addError("Invalid JSON in module.json");
            return;
        }

        if (!isset($data['name']) || $data['name'] !== $moduleName) {
            $this->addError("Module name mismatch in module.json");
        }

        if (!isset($data['description'])) {
            $this->addWarning("Missing description in module.json");
        }

        if (!isset($data['active']) || !$data['active']) {
            $this->addWarning("Module is not active in module.json");
        }
    }

    private function createBasicDoc(string $filePath, string $moduleName): void
    {
        $fileName = basename($filePath);
        
        if ($fileName === 'README.md') {
            $content = "# {$moduleName} Module\n\n";
            $content .= "## Description\n\n";
            $content .= "This module provides...\n\n";
            $content .= "## Installation\n\n";
            $content .= "Module is auto-loaded when active.\n\n";
            $content .= "## Usage\n\n";
            $content .= "TBD\n";
        } elseif ($fileName === 'CHANGELOG.md') {
            $content = "# Changelog - {$moduleName}\n\n";
            $content .= "## [Unreleased]\n\n";
            $content .= "### Added\n- Initial module structure\n\n";
        } else {
            $content = "# {$moduleName}\n\nGenerated documentation.";
        }

        File::put($filePath, $content);
    }

    private function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    private function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    private function addSuggestion(string $message): void
    {
        $this->suggestions[] = $message;
    }

    private function displaySummary(): void
    {
        $this->line("");
        $this->info("📊 Validation Summary");
        $this->line("====================");
        
        $this->line("Files validated: {$this->validatedFiles}/{$this->totalFiles}");
        $this->line("");

        if (!empty($this->errors)) {
            $this->error("❌ Errors (" . count($this->errors) . "):");
            foreach ($this->errors as $error) {
                $this->line("   • {$error}");
            }
            $this->line("");
        }

        if (!empty($this->warnings)) {
            $this->warn("⚠️  Warnings (" . count($this->warnings) . "):");
            foreach ($this->warnings as $warning) {
                $this->line("   • {$warning}");
            }
            $this->line("");
        }

        if (!empty($this->suggestions)) {
            $this->info("💡 Suggestions (" . count($this->suggestions) . "):");
            foreach ($this->suggestions as $suggestion) {
                $this->line("   • {$suggestion}");
            }
            $this->line("");
        }

        // Overall assessment
        if (empty($this->errors) && empty($this->warnings)) {
            $this->info("🎉 All modules are perfectly structured!");
        } elseif (empty($this->errors)) {
            $this->warn("⚠️  Modules have some warnings but no critical errors.");
        } else {
            $this->error("❌ Critical errors found. Please fix before proceeding.");
        }

        if ($this->option('fix')) {
            $this->line("");
            $this->info("🔧 Auto-fix was enabled. Some issues may have been resolved.");
        } else {
            $this->line("");
            $this->comment("💡 Tip: Use --fix flag to automatically fix some issues.");
        }
    }
}
