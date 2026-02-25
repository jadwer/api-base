<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\Contracts\GeneratorInterface;
use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use App\Console\Commands\ModuleGeneration\NamingHelper;
use Illuminate\Support\Facades\File;

class MigrationGenerator implements GeneratorInterface
{
    private int $entityIndex;

    public function __construct(int $entityIndex = 0)
    {
        $this->entityIndex = $entityIndex;
    }

    public function generate(ModuleConfig $module, EntityConfig $entity): string
    {
        $timestamp = date('Y_m_d_His', time() + ($this->entityIndex * 5));
        $filename = "{$timestamp}_create_{$entity->tableName}_table.php";
        $path = base_path("Modules/{$module->name}/Database/migrations/{$filename}");
        File::ensureDirectoryExists(dirname($path));

        $content = $this->buildContent($entity);
        File::put($path, $content);

        return $path;
    }

    private function buildContent(EntityConfig $entity): string
    {
        $columns = $this->buildColumns($entity);
        $foreignKeys = $this->buildForeignKeys($entity);
        $indexes = $this->buildIndexes($entity);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("{$entity->tableName}", function (Blueprint \$table) {
            \$table->id();
{$columns}{$foreignKeys}{$indexes}            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$entity->tableName}");
    }
};

PHP;
    }

    private function buildColumns(EntityConfig $entity): string
    {
        $lines = [];
        foreach ($entity->fields as $field) {
            $col = "            {$field->getMigrationColumn()}";

            if ($field->isNullable) {
                $col .= '->nullable()';
            }
            if ($field->hasDefault) {
                $defaultVal = $this->formatDefault($field->default);
                $col .= "->default({$defaultVal})";
            }

            $col .= ';';
            $lines[] = $col;
        }
        return implode("\n", $lines) . "\n";
    }

    private function buildForeignKeys(EntityConfig $entity): string
    {
        $lines = [];
        foreach ($entity->getBelongsToRelationships() as $rel) {
            $fk = NamingHelper::toForeignKey($rel['model']);
            $table = isset($rel['entity'])
                ? NamingHelper::toTableName($rel['entity'])
                : NamingHelper::toTableName($rel['model']);

            // For cross-module, use the model name to derive the table
            if (isset($rel['module'])) {
                $table = NamingHelper::toTableName($rel['model']);
            }

            $nullable = ($rel['nullable'] ?? false) ? '->nullable()' : '';
            $lines[] = "            \$table->foreignId('{$fk}'){$nullable}->constrained('{$table}')->onDelete('restrict');";
        }

        if (!empty($lines)) {
            return implode("\n", $lines) . "\n";
        }
        return '';
    }

    private function buildIndexes(EntityConfig $entity): string
    {
        $lines = [];
        foreach ($entity->fields as $field) {
            if ($field->isFilterable || $field->isSortable) {
                $lines[] = "            \$table->index('{$field->dbName}');";
            }
        }

        if (!empty($lines)) {
            return implode("\n", $lines) . "\n";
        }
        return '';
    }

    private function formatDefault(mixed $value): string
    {
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_string($value)) return "'{$value}'";
        if (is_null($value)) return 'null';
        return (string) $value;
    }
}
