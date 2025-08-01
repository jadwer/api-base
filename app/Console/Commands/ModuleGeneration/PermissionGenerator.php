<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Str;

class PermissionGenerator
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
     * Generate permissions seeder file
     */
    public function generatePermissionsSeeder(string $moduleName, array $permissionsConfig): void
    {
        $seederContent = $this->generatePermissionsSeederContent($moduleName, $permissionsConfig);
        
        $seederPath = base_path("Modules/{$moduleName}/Database/Seeders/PermissionsSeeder.php");
        
        file_put_contents($seederPath, $seederContent);
        
        $this->info("🔐 Generated permissions seeder");
    }

    /**
     * Generate permissions seeder content
     */
    public function generatePermissionsSeederContent(string $moduleName, array $permissionsConfig): string
    {
        $namespace = "Modules\\{$moduleName}\\Database\\Seeders";
        
        // Check if this is the new JSON format (permission -> roles mapping)
        if ($this->isNewFormat($permissionsConfig)) {
            return $this->generatePermissionsSeederFromNewFormat($namespace, $moduleName, $permissionsConfig);
        }
        
        // Legacy format handling
        $prefix = $permissionsConfig['prefix'] ?? strtolower($moduleName);
        $resources = $permissionsConfig['resources'] ?? [];
        $actions = $permissionsConfig['actions'] ?? ['index', 'show', 'store', 'update', 'destroy'];
        $roles = $permissionsConfig['roles'] ?? [];

        // Extract all unique permissions from roles configuration
        $allPermissions = $this->extractUniquePermissionsFromRoles($roles, $resources, $actions, $prefix);
        
        // Generate permissions creation code
        $permissionsCode = $this->generatePermissionsCreationCodeFromList($allPermissions);
        
        // Generate role assignments code
        $roleAssignmentsCode = $this->generateRoleAssignmentsCode($prefix, $resources, $actions, $roles);

        return "<?php

namespace {$namespace};

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
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

    /**
     * Extract all unique permissions from roles configuration 
     */
    public function extractUniquePermissionsFromRoles(array $roles, array $resources, array $actions, string $prefix): array
    {
        $permissions = [];
        
        foreach ($roles as $roleName => $rolePermissions) {            
            foreach ($rolePermissions as $permission) {
                if ($permission === 'all') {
                    // Add all resource.action combinations
                    foreach ($resources as $resource) {
                        foreach ($actions as $action) {
                            $permissions[] = "{$prefix}.{$resource}.{$action}";
                        }
                    }
                } elseif (str_contains($permission, '.*')) {
                    // Add all actions for specific resource
                    $resource = str_replace('.*', '', $permission);
                    foreach ($actions as $action) {
                        $permissions[] = "{$prefix}.{$resource}.{$action}";
                    }
                } else {
                    // Add specific permission as-is (no prefix)
                    $permissions[] = $permission;
                }
            }
        }
        
        return array_unique($permissions);
    }
    
    /**
     * Generate permissions creation code from a list of permission names
     */
    public function generatePermissionsCreationCodeFromList(array $permissions): string
    {
        $permissionStatements = [];
        
        foreach ($permissions as $permission) {
            $permissionStatements[] = "        Permission::firstOrCreate([
            'name' => '{$permission}',
            'guard_name' => 'api',
        ]);";
        }

        return implode("\n", $permissionStatements);
    }

    /**
     * Generate role assignments code
     */
    public function generateRoleAssignmentsCode($prefix, $resources, $actions, $roles): string
    {
        $assignments = [];
        
        // Ensure essential roles are included with sensible defaults
        $roles = $this->ensureEssentialRoles($roles, $resources);
        
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
                    // Grant specific permission (use as-is from config, no prefix needed)
                    $assignments[] = "            \$role{$roleName}->givePermissionTo('{$permission}');";
                }
            }
            
            $assignments[] = "        }";
        }
        
        return implode("\n", $assignments);
    }

    /**
     * Ensure essential roles are included with sensible defaults
     */
    public function ensureEssentialRoles(array $roles, array $resources): array
    {
        // Auto-add tech role with read-only permissions if not present
        if (!isset($roles['tech']) && !empty($resources)) {
            $readOnlyPermissions = [];
            foreach ($resources as $resource) {
                $readOnlyPermissions[] = "{$resource}.index";
                $readOnlyPermissions[] = "{$resource}.show"; 
            }
            $roles['tech'] = $readOnlyPermissions;
            $this->info("ℹ️  Auto-added 'tech' role with read-only permissions");
        }
        
        return $roles;
    }

    /**
     * Check if permissions config uses new format (permission -> roles mapping)
     */
    private function isNewFormat(array $permissionsConfig): bool
    {
        // New format: direct permission -> roles mapping without nested structure
        // e.g., "test-entities.index": ["admin", "customer"]
        foreach ($permissionsConfig as $key => $value) {
            if (is_string($key) && str_contains($key, '.') && is_array($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate permissions seeder content from new format
     */
    private function generatePermissionsSeederFromNewFormat(string $namespace, string $moduleName, array $permissionsConfig): string
    {
        // Extract all unique permissions
        $allPermissions = array_keys($permissionsConfig);
        
        // Generate permissions creation code
        $permissionsCode = '';
        foreach ($allPermissions as $permission) {
            $permissionsCode .= "        Permission::firstOrCreate([\n";
            $permissionsCode .= "            'name' => '{$permission}',\n";
            $permissionsCode .= "            'guard_name' => 'api',\n";
            $permissionsCode .= "        ]);\n";
        }
        
        // Generate role assignments code
        $roleAssignmentsCode = $this->generateRoleAssignmentsFromNewFormat($permissionsConfig);

        return "<?php

namespace {$namespace};

use Illuminate\\Database\\Seeder;
use Spatie\\Permission\\Models\\Permission;
use Spatie\\Permission\\Models\\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        \$this->command->info('🔐 Seeding {$moduleName} permissions...');
        
        // Create permissions
{$permissionsCode}
        
        // Assign permissions to roles
{$roleAssignmentsCode}
        
        \$this->command->info('✅ {$moduleName} permissions seeded successfully!');
    }
}\n";
    }

    /**
     * Generate role assignments from new format
     */
    private function generateRoleAssignmentsFromNewFormat(array $permissionsConfig): string
    {
        $roleAssignments = [];
        
        // Group permissions by role
        $rolePermissions = [];
        foreach ($permissionsConfig as $permission => $roles) {
            foreach ($roles as $role) {
                if (!isset($rolePermissions[$role])) {
                    $rolePermissions[$role] = [];
                }
                $rolePermissions[$role][] = $permission;
            }
        }
        
        // Generate assignments code
        $assignments = [];
        foreach ($rolePermissions as $roleName => $permissions) {
            $assignments[] = "\n        // {$roleName} role permissions";
            $assignments[] = "        \$role{$roleName} = Role::where('name', '{$roleName}')->where('guard_name', 'api')->first();";
            $assignments[] = "        if (\$role{$roleName}) {";
            
            foreach ($permissions as $permission) {
                $assignments[] = "            \$role{$roleName}->givePermissionTo('{$permission}');";
            }
            
            $assignments[] = "        }";
        }
        
        return implode("\n", $assignments);
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
}