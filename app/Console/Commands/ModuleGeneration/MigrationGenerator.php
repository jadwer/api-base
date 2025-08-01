<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MigrationGenerator
{
    private $command;

    public function __construct($command = null)
    {
        $this->command = $command;
    }

    /**
     * Generate migration for entity
     */
    public function generateEntityMigration(string $moduleName, array $entity): void
    {
        $entityName = $entity['name'];
        $tableName = $entity['tableName'];
        $fields = $entity['fields'];
        
        // Generate unique timestamp for migration
        $timestamp = Carbon::now()->format('Y_m_d_His');
        Carbon::now()->addSecond(); // Ensure unique timestamps
        
        $migrationName = "create_{$tableName}_table";
        $className = Str::studly($migrationName);
        $fileName = "{$timestamp}_{$migrationName}.php";
        
        $migrationDir = "Modules/{$moduleName}/Database/Migrations";
        $migrationPath = "{$migrationDir}/{$fileName}";
        
        if (!File::isDirectory($migrationDir)) {
            File::makeDirectory($migrationDir, 0755, true);
        }
        
        $migrationContent = $this->generateMigrationContent($className, $tableName, $fields);
        
        File::put($migrationPath, $migrationContent);
        $this->info("📄 Generated migration for {$entityName}: {$fileName}");
    }

    /**
     * Generate migration file content
     */
    private function generateMigrationContent(string $className, string $tableName, array $fields): string
    {
        $fieldsCode = $this->generateMigrationFields($fields);
        
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(\"{$tableName}\", function (Blueprint \$table) {
            \$table->id();
{$fieldsCode}
            \$table->json('metadata')->nullable();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(\"{$tableName}\");
    }
};";
    }

    /**
     * Generate migration fields code
     */
    private function generateMigrationFields(array $fields): string
    {
        $lines = [];
        
        foreach ($fields as $field) {
            $line = $this->generateFieldLine($field);
            if ($line) {
                $lines[] = "            {$line}";
            }
        }
        
        return implode("\n", $lines);
    }

    /**
     * Generate individual field line for migration
     */
    private function generateFieldLine(array $field): string
    {
        $line = "\$table->{$field['type']}('{$field['name']}')";
        
        // Add nullable constraint
        if (isset($field['nullable']) && $field['nullable']) {
            $line .= "->nullable()";
        }
        
        // Add unique constraint  
        if (isset($field['unique']) && $field['unique']) {
            $line .= "->unique()";
        }
        
        // Add default value
        if (isset($field['default'])) {
            $defaultValue = is_string($field['default']) ? "'{$field['default']}'" : $field['default'];
            $line .= "->default({$defaultValue})";
        }
        
        // Handle foreign key relationships
        if ($field['type'] === 'foreignId' && Str::endsWith($field['name'], '_id')) {
            $relatedTable = $this->inferTableNameFromForeignKey($field['name']);
            $line .= "->constrained('{$relatedTable}')->onDelete('restrict')";
        }
        
        $line .= ";";
        
        return $line;
    }

    /**
     * Infer table name from foreign key field name
     */
    private function inferTableNameFromForeignKey(string $fieldName): string
    {
        // Remove '_id' suffix and pluralize
        $baseEntity = Str::beforeLast($fieldName, '_id');
        return Str::snake(Str::plural(Str::studly($baseEntity)));
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