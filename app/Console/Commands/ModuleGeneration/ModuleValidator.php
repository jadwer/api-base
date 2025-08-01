<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleValidator
{
    /**
     * Console command instance for output
     */
    private $command;

    public function __construct($command = null)
    {
        $this->command = $command;
    }

    /**
     * Validate entity names for conflicts with existing models
     * 
     * @param array $entitiesConfig
     * @param string $moduleName
     * @return void
     * @throws \Exception
     */
    public function validateEntityNamesForConflicts(array $entitiesConfig, string $moduleName): void
    {
        $this->info("🔍 Validating entity names for conflicts...");
        
        // Get all existing models from other modules
        $existingModels = $this->discoverModuleModels();
        $conflicts = [];
        $warnings = [];
        
        foreach ($entitiesConfig as $entityName => $entityData) {
            $modelName = $entityData['name'];
            
            // Check if model name already exists in another module
            if (isset($existingModels[$modelName])) {
                $existingModule = $existingModels[$modelName];
                if ($existingModule !== $moduleName) {
                    $conflicts[] = [
                        'model' => $modelName,
                        'existing_module' => $existingModule,
                        'suggested' => $this->suggestAlternativeName($modelName, $existingModels)
                    ];
                }
            }
            
            // Check for potential permission conflicts
            $resourceType = Str::kebab(Str::plural($modelName));
            $this->checkPermissionConflicts($resourceType, $modelName, $warnings);
            
            // Check for route conflicts
            $this->checkRouteConflicts($resourceType, $modelName, $warnings);
        }
        
        // Display warnings first
        foreach ($warnings as $warning) {
            $this->warn("⚠️  {$warning}");
        }
        
        // Handle conflicts
        if (!empty($conflicts)) {
            $this->error("❌ CONFLICTOS DETECTADOS:");
            $this->error("Los siguientes nombres de entidades ya existen en otros módulos:");
            $this->newLine();
            
            foreach ($conflicts as $conflict) {
                $this->error("  • '{$conflict['model']}' ya existe en módulo '{$conflict['existing_module']}'");
                $this->info("    💡 Sugerencia: '{$conflict['suggested']}'");
            }
            
            $this->newLine();
            $this->error("🚫 No se puede continuar con la generación del módulo.");
            $this->error("Por favor, modifica la configuración para usar nombres únicos.");
            $this->newLine();
            $this->info("📝 Ejemplo de nombres únicos:");
            foreach ($conflicts as $conflict) {
                $this->info("    {$conflict['model']} → {$conflict['suggested']}");
            }
            
            throw new \Exception("Entity name conflicts detected. Please use unique names.");
        }
        
        $this->info("✅ No se detectaron conflictos de nombres");
    }

    /**
     * Discover all models in existing modules
     * Returns array with model name => module name mapping
     */
    public function discoverModuleModels(): array
    {
        $models = [];
        $modulesPath = base_path('Modules');
        
        if (!File::isDirectory($modulesPath)) {
            return $models;
        }
        
        $modules = File::directories($modulesPath);
        
        foreach ($modules as $moduleDir) {
            $moduleName = basename($moduleDir);
            $modelsPath = $moduleDir . '/app/Models';
            
            if (File::isDirectory($modelsPath)) {
                $modelFiles = File::files($modelsPath);
                
                foreach ($modelFiles as $modelFile) {
                    $modelName = pathinfo($modelFile->getFilename(), PATHINFO_FILENAME);
                    $models[$modelName] = $moduleName;
                }
            }
        }
        
        return $models;
    }
    
    /**
     * Suggest alternative name for conflicting entity
     */
    private function suggestAlternativeName(string $modelName, array $existingModels): string
    {
        // Try prefixing with module-specific prefixes
        $prefixes = ['New', 'Alt', 'Custom', 'Extended'];
        
        foreach ($prefixes as $prefix) {
            $suggestion = $prefix . $modelName;
            if (!isset($existingModels[$suggestion])) {
                return $suggestion;
            }
        }
        
        // If all prefixes are taken, add a number
        $counter = 2;
        while (isset($existingModels[$modelName . $counter])) {
            $counter++;
        }
        
        return $modelName . $counter;
    }
    
    /**
     * Check for permission conflicts
     */
    private function checkPermissionConflicts(string $resourceType, string $modelName, array &$warnings): void
    {
        // Check if similar permission patterns exist
        $commonConflictPatterns = [
            'users' => 'User management permissions already exist',
            'products' => 'Product permissions already exist - consider: new-products, custom-products',
            'categories' => 'Category permissions already exist - consider: new-categories, custom-categories', 
            'brands' => 'Brand permissions already exist - consider: new-brands, custom-brands',
            'units' => 'Unit permissions already exist - consider: new-units, custom-units'
        ];
        
        if (isset($commonConflictPatterns[$resourceType])) {
            $warnings[] = $commonConflictPatterns[$resourceType];
        }
    }
    
    /**
     * Check for route conflicts  
     */
    private function checkRouteConflicts(string $resourceType, string $modelName, array &$warnings): void
    {
        // Check for common route conflicts
        $commonRoutes = [
            'users', 'products', 'categories', 'brands', 'units', 
            'orders', 'items', 'customers', 'suppliers'
        ];
        
        if (in_array($resourceType, $commonRoutes)) {
            $warnings[] = "Route '/api/v1/{$resourceType}' may conflict with existing routes";
        }
    }

    /**
     * Helper methods for console output
     */
    private function info(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }

    private function warn(string $message): void
    {
        if ($this->command) {
            $this->command->warn($message);
        }
    }

    private function error(string $message): void
    {
        if ($this->command) {
            $this->command->error($message);
        }
    }

    private function newLine(): void
    {
        if ($this->command) {
            $this->command->newLine();
        }
    }
}