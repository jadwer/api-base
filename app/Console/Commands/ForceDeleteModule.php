<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Console\Commands\ModuleGeneration\IntegrationManager;

class ForceDeleteModule extends Command
{
    protected $signature = 'module:force-delete {name : The module name to delete}';
    protected $description = 'Forcefully delete a module with complete cleanup';

    private IntegrationManager $integrationManager;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $moduleName = $this->argument('name');
        
        $this->info("🗑️ Force deleting module: {$moduleName}");
        
        if (!$this->confirmDeletion($moduleName)) {
            $this->warn('Module deletion cancelled.');
            return 0;
        }
        
        try {
            // Step 1: Check if module exists
            $modulePath = base_path("Modules/{$moduleName}");
            if (!File::isDirectory($modulePath)) {
                $this->warn("Module {$moduleName} does not exist.");
                return 1;
            }
            
            // Step 2: Clean integration files
            $this->info('🧹 Cleaning integration files...');
            $this->integrationManager = new IntegrationManager($moduleName, $this);
            $this->integrationManager->cleanModuleIntegration();
            
            // Step 3: Remove from modules_statuses.json
            $this->info('📝 Updating modules_statuses.json...');
            $this->removeFromModulesStatus($moduleName);
            
            // Step 4: Delete module directory
            $this->info('📁 Deleting module directory...');
            $this->deleteModuleDirectory($modulePath);
            
            // Step 5: Clear composer autoload
            $this->info('🔄 Clearing composer autoload...');
            exec('composer dump-autoload', $output, $returnVar);
            if ($returnVar === 0) {
                $this->info('✓ Composer autoload cleared');
            } else {
                $this->warn('⚠️ Could not clear composer autoload automatically');
            }
            
            $this->info("✅ Module {$moduleName} deleted successfully!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error deleting module: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function confirmDeletion(string $moduleName): bool
    {
        return $this->confirm("Are you sure you want to PERMANENTLY delete module '{$moduleName}'? This action cannot be undone.");
    }
    
    private function removeFromModulesStatus(string $moduleName): void
    {
        $statusFile = base_path('modules_statuses.json');
        
        if (File::exists($statusFile)) {
            $statuses = json_decode(File::get($statusFile), true);
            
            if (isset($statuses[$moduleName])) {
                unset($statuses[$moduleName]);
                File::put($statusFile, json_encode($statuses, JSON_PRETTY_PRINT));
                $this->info("✓ Removed {$moduleName} from modules_statuses.json");
            }
        }
    }
    
    private function deleteModuleDirectory(string $modulePath): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows-specific deletion with retries
            $attempts = 0;
            $maxAttempts = 3;
            
            while ($attempts < $maxAttempts && File::isDirectory($modulePath)) {
                try {
                    // Try Laravel's method first
                    File::deleteDirectory($modulePath);
                    
                    if (!File::isDirectory($modulePath)) {
                        break;
                    }
                    
                    // If still exists, try Windows rmdir
                    exec('rmdir /s /q "' . addslashes($modulePath) . '"', $output, $returnVar);
                    
                    $attempts++;
                    if ($attempts < $maxAttempts && File::isDirectory($modulePath)) {
                        $this->warn("Deletion attempt {$attempts} failed, retrying...");
                        sleep(1);
                    }
                } catch (\Exception $e) {
                    $attempts++;
                    if ($attempts >= $maxAttempts) {
                        throw $e;
                    }
                    sleep(1);
                }
            }
            
            if (File::isDirectory($modulePath)) {
                throw new \Exception("Could not delete module directory after {$maxAttempts} attempts");
            }
        } else {
            // Unix/Linux deletion
            File::deleteDirectory($modulePath);
        }
        
        $this->info("✓ Module directory deleted");
    }
}