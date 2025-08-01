<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class IntegrationManager
{
    private string $moduleName;
    private string $moduleNamespace;
    private $command;
    
    public function __construct(string $moduleName, $command = null)
    {
        $this->moduleName = $moduleName;
        $this->moduleNamespace = "Modules\\{$moduleName}";
        $this->command = $command;
    }
    
    /**
     * Display info message through command or fallback to error_log
     */
    private function info(string $message): void
    {
        if ($this->command && method_exists($this->command, 'info')) {
            $this->command->info($message);
        }
    }
    
    /**
     * Display warn message through command or fallback to error_log
     */
    private function warn(string $message): void
    {
        if ($this->command && method_exists($this->command, 'warn')) {
            $this->command->warn($message);
        }
    }
    
    /**
     * Integrate module completely into the application
     */
    public function integrateModuleCompletely(array $entities): void
    {
        $this->info("🔗 Integrating module {$this->moduleName} completely...");
        
        // 1. Add schemas to Server.php
        $this->addSchemasToServer($entities);
        $this->info("✓ Added schemas to Server.php for {$this->moduleName}");
        
        // 2. Add seeder to DatabaseSeeder
        $this->addSeederToDatabase();
        $this->info("✓ Added seeder to DatabaseSeeder.php for {$this->moduleName}");
        
        // 3. Add seeder to TestCase
        $this->addSeederToTestCase();
        $this->info("✓ Added seeder to TestCase.php for {$this->moduleName}");
        
        $this->info("✅ Module {$this->moduleName} integrated successfully");
    }
    
    /**
     * Add schemas and authorizers to Server.php
     */
    private function addSchemasToServer(array $entities): void
    {
        $serverPath = base_path('app/JsonApi/V1/Server.php');
        if (!File::exists($serverPath)) {
            throw new \Exception("Server.php not found at: {$serverPath}");
        }
        
        $content = File::get($serverPath);
        
        // Add imports
        $content = $this->addSchemaImports($content, $entities);
        
        // Add schemas to array
        $content = $this->addSchemasToArray($content, $entities);
        
        // Add authorizers
        $content = $this->addAuthorizersToArray($content, $entities);
        
        File::put($serverPath, $content);
    }
    
    /**
     * Add schema imports to Server.php
     */
    private function addSchemaImports(string $content, array $entities): string
    {
        $imports = [];
        
        foreach ($entities as $entityName => $entityData) {
            // Use entityData['name'] to get the actual entity name
            $actualEntityName = $entityData['name'] ?? $entityName;
            $entityPlural = Str::plural($actualEntityName);
            $imports[] = "use {$this->moduleNamespace}\\JsonApi\\V1\\{$entityPlural}\\{$actualEntityName}Schema;";
        }
        
        // Find the last use statement and add our imports after it
        $lines = explode("\n", $content);
        $lastUseIndex = -1;
        
        foreach ($lines as $index => $line) {
            if (Str::startsWith(trim($line), 'use ')) {
                $lastUseIndex = $index;
            }
        }
        
        if ($lastUseIndex !== -1) {
            array_splice($lines, $lastUseIndex + 1, 0, $imports);
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Add schemas to the schemas array in Server.php
     */
    private function addSchemasToArray(string $content, array $entities): string
    {
        $lines = explode("\n", $content);
        $schemaArrayEndIndex = -1;
        
        // Find the end of the schemas array
        $inSchemasArray = false;
        foreach ($lines as $index => $line) {
            if (preg_match('/\$schemas = \[/', $line)) {
                $inSchemasArray = true;
                continue;
            }
            
            if ($inSchemasArray && preg_match('/^\s*\];\s*$/', $line)) {
                $schemaArrayEndIndex = $index;
                break;
            }
        }
        
        if ($schemaArrayEndIndex !== -1) {
            $schemaEntries = [];
            $schemaEntries[] = "";
            $schemaEntries[] = "            // {$this->moduleName} Module";
            
            foreach ($entities as $entityName => $entityData) {
                // Use entityData['name'] to get the actual entity name
                $actualEntityName = $entityData['name'] ?? $entityName;
                $schemaEntries[] = "            {$actualEntityName}Schema::class,";
            }
            $schemaEntries[] = "";
            
            array_splice($lines, $schemaArrayEndIndex, 0, $schemaEntries);
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Add authorizers to the authorizers array in Server.php
     */
    private function addAuthorizersToArray(string $content, array $entities): string
    {
        $lines = explode("\n", $content);
        $authorizersArrayEndIndex = -1;
        
        // Find the end of the authorizers array
        $inAuthorizersArray = false;
        foreach ($lines as $index => $line) {
            if (preg_match('/\$authorizers = \[/', $line)) {
                $inAuthorizersArray = true;
                continue;
            }
            
            if ($inAuthorizersArray && preg_match('/^\s*\];\s*$/', $line)) {
                $authorizersArrayEndIndex = $index;
                break;
            }
        }
        
        if ($authorizersArrayEndIndex !== -1) {
            $authorizerEntries = [];
            $authorizerEntries[] = "            ";
            $authorizerEntries[] = "            // {$this->moduleName} Module";
            
            foreach ($entities as $entityName => $entityData) {
                // Use entityData['name'] to get the actual entity name
                $actualEntityName = $entityData['name'] ?? $entityName;
                $resourceType = Str::kebab(Str::plural($actualEntityName));
                $entityPlural = Str::plural($actualEntityName);
                $authorizerEntries[] = "            '{$resourceType}' => \\{$this->moduleNamespace}\\JsonApi\\V1\\{$entityPlural}\\{$actualEntityName}Authorizer::class,";
            }
            
            array_splice($lines, $authorizersArrayEndIndex, 0, $authorizerEntries);
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Add seeder to DatabaseSeeder.php
     */
    private function addSeederToDatabase(): void
    {
        $seederPath = base_path('database/seeders/DatabaseSeeder.php');
        if (!File::exists($seederPath)) {
            throw new \Exception("DatabaseSeeder.php not found at: {$seederPath}");
        }
        
        $content = File::get($seederPath);
        
        // Find the end of the $this->call array
        $lines = explode("\n", $content);
        $callArrayEndIndex = -1;
        
        $inCallArray = false;
        foreach ($lines as $index => $line) {
            if (preg_match('/\$this->call\(\[/', $line)) {
                $inCallArray = true;
                continue;
            }
            
            if ($inCallArray && preg_match('/^\s*\]\);\s*$/', $line)) {
                $callArrayEndIndex = $index;
                break;
            }
        }
        
        if ($callArrayEndIndex !== -1) {
            $seederEntry = "            \\{$this->moduleNamespace}\\Database\\Seeders\\{$this->moduleName}DatabaseSeeder::class,";
            array_splice($lines, $callArrayEndIndex, 0, [$seederEntry]);
        }
        
        $content = implode("\n", $lines);
        File::put($seederPath, $content);
    }
    
    /**
     * Add seeder to TestCase.php
     */
    private function addSeederToTestCase(): void
    {
        $testCasePath = base_path('tests/TestCase.php');
        if (!File::exists($testCasePath)) {
            throw new \Exception("TestCase.php not found at: {$testCasePath}");
        }
        
        $content = File::get($testCasePath);
        
        // Find the setUp method and add our seeder after PermissionManager
        $lines = explode("\n", $content);
        $permissionManagerIndex = -1;
        
        foreach ($lines as $index => $line) {
            if (preg_match("/module:seed.*PermissionManager/", $line)) {
                $permissionManagerIndex = $index;
                break;
            }
        }
        
        if ($permissionManagerIndex !== -1) {
            $seederEntry = "        \$this->artisan('module:seed', ['module' => '{$this->moduleName}']);";
            array_splice($lines, $permissionManagerIndex + 1, 0, [$seederEntry]);
        }
        
        $content = implode("\n", $lines);
        File::put($testCasePath, $content);
    }
    
    /**
     * Clean module integration from all files
     */
    public function cleanModuleIntegration(): void
    {
        $this->cleanServerSchemas();
        $this->info("✓ Cleaned Server.php schemas and authorizers for {$this->moduleName}");
        
        $this->cleanDatabaseSeeder();
        $this->info("✓ Cleaned DatabaseSeeder.php for {$this->moduleName}");
        
        $this->cleanTestCase();
        $this->info("✓ Cleaned TestCase.php for {$this->moduleName}");
    }
    
    /**
     * Clean Server.php schemas and authorizers for specific module
     */
    private function cleanServerSchemas(): void
    {
        $serverPath = base_path('app/JsonApi/V1/Server.php');
        if (!File::exists($serverPath)) {
            return;
        }

        $content = File::get($serverPath);
        $originalContent = $content;
        
        // Step 1: Extract schemas BEFORE cleaning imports (so we can identify them)
        $moduleSchemas = $this->extractModuleSchemas($content);
        
        // Step 2: Clean schemas array first (while imports still exist for context)
        $content = $this->cleanServerSchemasArray($content, $moduleSchemas);
        
        // Step 3: Clean imports line by line
        $content = $this->cleanServerImports($content);
        
        // Step 4: Clean authorizers array line by line  
        $content = $this->cleanServerAuthorizersArray($content);
        
        // Step 5: Clean up multiple empty lines
        $content = preg_replace("/\n\s*\n\s*\n/", "\n\n", $content);
        
        // Step 6: Validation - ensure we didn't break the syntax
        if ($this->validateServerSyntax($content)) {
            File::put($serverPath, $content);
        } else {
            File::put($serverPath, $originalContent);
            throw new \Exception("Server.php cleanup would cause syntax errors");
        }
    }
    
    /**
     * Clean imports from Server.php for specific module
     */
    private function cleanServerImports(string $content): string
    {
        $lines = explode("\n", $content);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            // Skip lines that import from the target module
            $trimmedLine = trim($line);
            $escapedNamespace = preg_quote($this->moduleNamespace, '/');
            if (preg_match("/^use {$escapedNamespace}\\\\.*?;$/", $trimmedLine)) {
                $this->info("🗑️ Removing import: {$trimmedLine}");
                continue;
            }
            $cleanedLines[] = $line;
        }
        
        return implode("\n", $cleanedLines);
    }
    
    /**
     * Clean schemas array from Server.php for specific module
     */
    private function cleanServerSchemasArray(string $content, array $moduleSchemas = []): string
    {
        $lines = explode("\n", $content);
        $cleanedLines = [];
        $inSchemasArray = false;
        
        foreach ($lines as $line) {
            // Detect start of schemas array
            if (preg_match('/\$schemas = \[/', $line)) {
                $inSchemasArray = true;
                $cleanedLines[] = $line;
                continue;
            }
            
            // Detect end of schemas array
            if ($inSchemasArray && preg_match('/^\s*\];\s*$/', $line)) {
                $inSchemasArray = false;
                $cleanedLines[] = $line;
                continue;
            }
            
            // If we're in the schemas array, check for module-specific schemas
            if ($inSchemasArray) {
                // Skip module comments
                if (preg_match("/\/\/ {$this->moduleName} Module/", $line)) {
                    $this->info("🗑️ Removing schema comment: " . trim($line));
                    continue;
                }
                
                // Skip schema classes from the target module (full namespace)
                $escapedNamespace = preg_quote($this->moduleNamespace, '/');
                if (preg_match("/\\\\?{$escapedNamespace}\\\\.*?Schema::class,?/", $line)) {
                    $this->info("🗑️ Removing schema (full namespace): " . trim($line));
                    continue;
                }
                
                // Skip schema classes from the target module (short form using imports)
                $shouldSkip = false;
                foreach ($moduleSchemas as $schemaClass) {
                    if (preg_match("/{$schemaClass}::class,?/", $line)) {
                        $this->info("🗑️ Removing schema (short form): " . trim($line));
                        $shouldSkip = true;
                        break;
                    }
                }
                if ($shouldSkip) {
                    continue;
                }
            }
            
            $cleanedLines[] = $line;
        }
        
        return implode("\n", $cleanedLines);
    }
    
    /**
     * Extract schema class names that belong to this module by analyzing imports
     */
    private function extractModuleSchemas(string $content): array
    {
        $schemas = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            $escapedNamespace = preg_quote($this->moduleNamespace, '/');
            if (preg_match("/^use {$escapedNamespace}\\\\.*?\\\\(\w+Schema);$/", $trimmedLine, $matches)) {
                $schemas[] = $matches[1];
                $this->info("🔍 Found module schema: {$matches[1]}");
            }
        }
        
        return $schemas;
    }
    
    /**
     * Clean authorizers array from Server.php for specific module
     */
    private function cleanServerAuthorizersArray(string $content): string
    {
        $lines = explode("\n", $content);
        $cleanedLines = [];
        $inAuthorizersArray = false;
        
        foreach ($lines as $line) {
            // Detect start of authorizers array
            if (preg_match('/\$authorizers = \[/', $line)) {
                $inAuthorizersArray = true;
                $cleanedLines[] = $line;
                continue;
            }
            
            // Detect end of authorizers array
            if ($inAuthorizersArray && preg_match('/^\s*\];\s*$/', $line)) {
                $inAuthorizersArray = false;
                $cleanedLines[] = $line;
                continue;
            }
            
            // If we're in the authorizers array, check for module-specific authorizers
            if ($inAuthorizersArray) {
                // Skip module comments
                if (preg_match("/\/\/ {$this->moduleName} Module/", $line)) {
                    continue;
                }
                
                // Skip authorizer mappings from the target module
                $escapedNamespace = preg_quote($this->moduleNamespace, '/');
                if (preg_match("/'[^']*' => \\\\{$escapedNamespace}\\\\.*?Authorizer::class,?/", $line)) {
                    $this->info("🗑️ Removing authorizer: " . trim($line));
                    continue;
                }
            }
            
            $cleanedLines[] = $line;
        }
        
        return implode("\n", $cleanedLines);
    }
    
    /**
     * Validate that Server.php content has valid PHP syntax
     */
    private function validateServerSyntax(string $content): bool
    {
        // Basic syntax checks
        if (substr_count($content, 'protected function allSchemas()') !== 1) {
            return false;
        }
        
        if (substr_count($content, 'protected function authorizers()') !== 1) {
            return false;
        }
        
        // Check that arrays are properly formed
        if (!preg_match('/protected function allSchemas\(\).*?\{.*?\$schemas = \[.*?\];.*?return \$schemas;.*?\}/s', $content)) {
            return false;
        }
        
        if (!preg_match('/protected function authorizers\(\).*?\{.*?\$authorizers = \[.*?\];.*?return \$authorizers;.*?\}/s', $content)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Clean DatabaseSeeder.php for specific module
     */
    private function cleanDatabaseSeeder(): void
    {
        $seederPath = base_path('database/seeders/DatabaseSeeder.php');
        if (!File::exists($seederPath)) {
            return;
        }

        $content = File::get($seederPath);
        $originalContent = $content;
        
        // Use line-by-line processing for better precision
        $lines = explode("\n", $content);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            // Check if line contains the module's database seeder
            $seederString = "\\Modules\\{$this->moduleName}\\Database\\Seeders\\{$this->moduleName}DatabaseSeeder::class";
            
            if (strpos($line, $seederString) !== false) {
                $this->info("🗑️ Removing seeder: " . trim($line));
                // If the line only contains this seeder, remove it completely
                $trimmedLine = trim($line);
                if ($trimmedLine === $seederString . ',' || $trimmedLine === $seederString) {
                    continue; // Skip this line
                }
                // If the line contains multiple seeders stuck together, clean only the specific module
                else {
                    $cleanedLine = str_replace($seederString . ',', '', $line);
                    $cleanedLine = str_replace($seederString, '', $cleanedLine);
                    $cleanedLines[] = $cleanedLine;
                }
            } else {
                $cleanedLines[] = $line;
            }
        }
        
        $newContent = implode("\n", $cleanedLines);
        
        // Validation before saving
        if ($this->validateDatabaseSeederSyntax($newContent)) {
            File::put($seederPath, $newContent);
        } else {
            File::put($seederPath, $originalContent);
            throw new \Exception("DatabaseSeeder.php cleanup would cause syntax errors");
        }
    }
    
    /**
     * Validate that DatabaseSeeder.php content has valid structure
     */
    private function validateDatabaseSeederSyntax(string $content): bool
    {
        // Check that call method exists
        if (!preg_match('/\$this->call\(\[/', $content)) {
            return false;
        }
        
        // Check that PermissionManager seeder exists (it should always be there)
        if (!preg_match("/PermissionManager.*DatabaseSeeder/", $content)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Clean TestCase.php for specific module
     */
    private function cleanTestCase(): void
    {
        $testCasePath = base_path('tests/TestCase.php');
        if (!File::exists($testCasePath)) {
            return;
        }

        $content = File::get($testCasePath);
        $originalContent = $content;
        
        // Use line-by-line processing for better precision
        $lines = explode("\n", $content);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            // Check if line contains the module's test seeder call
            $seederCall = "\$this->artisan('module:seed', ['module' => '{$this->moduleName}']);";
            
            if (strpos($line, $seederCall) !== false) {
                $this->info("🗑️ Removing test seeder: " . trim($line));
                // If the line only contains this seeder, remove it completely
                $trimmedLine = trim($line);
                if ($trimmedLine === $seederCall) {
                    continue; // Skip this line
                }
                // If the line contains multiple commands stuck together, clean only the specific module
                else {
                    $cleanedLine = str_replace($seederCall, '', $line);
                    $cleanedLines[] = $cleanedLine;
                }
            } else {
                $cleanedLines[] = $line;
            }
        }
        
        $newContent = implode("\n", $cleanedLines);
        
        // Validation before saving
        if ($this->validateTestCaseSyntax($newContent)) {
            File::put($testCasePath, $newContent);
        } else {
            File::put($testCasePath, $originalContent);
            throw new \Exception("TestCase.php cleanup would cause syntax errors");
        }
    }
    
    /**
     * Validate that TestCase.php content has valid structure
     */
    private function validateTestCaseSyntax(string $content): bool
    {
        // Check that setUp method exists
        if (!preg_match('/protected function setUp\(\): void/', $content)) {
            return false;
        }
        
        // Check that PermissionManager seeder exists (it should always be there)
        if (!preg_match("/module:seed.*PermissionManager/", $content)) {
            return false;
        }
        
        return true;
    }
}