<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SchemaGenerator
{
    private $command;
    
    public function __construct($command = null)
    {
        $this->command = $command;
    }

    /**
     * Generate JSON:API Schema for entity
     */
    public function generateEntitySchema(string $moduleName, array $entity, array $relationships = []): void
    {
        $entityName = $entity['name'];
        $fields = $entity['fields'];
        
        $schemaDir = base_path("Modules/{$moduleName}/app/JsonApi/V1/" . Str::plural($entityName));
        $schemaPath = "{$schemaDir}/{$entityName}Schema.php";
        
        if (!File::isDirectory($schemaDir)) {
            File::makeDirectory($schemaDir, 0755, true);
        }
        
        $schemaContent = $this->generateSchemaContent($moduleName, $entityName, $fields, $relationships);
        
        File::put($schemaPath, $schemaContent);
        $this->info("📄 Generated schema for {$entityName}");
    }

    /**
     * Generate schema file content
     */
    private function generateSchemaContent(string $moduleName, string $entityName, array $fields, array $relationships): string
    {
        $namespace = "Modules\\{$moduleName}\\JsonApi\\V1\\" . Str::plural($entityName);
        $fieldsCode = $this->generateSchemaFields($fields);
        $filtersCode = $this->generateSchemaFilters($fields);
        $sortablesCode = $this->generateSchemaSortables($fields);
        $resourceType = Str::kebab(Str::plural($entityName));
        
        return "<?php

namespace {$namespace};

use LaravelJsonApi\\Eloquent\\Contracts\\Paginator;
use LaravelJsonApi\\Eloquent\\Fields\\DateTime;
use LaravelJsonApi\\Eloquent\\Fields\\ID;
use LaravelJsonApi\\Eloquent\\Fields\\Str;
use LaravelJsonApi\\Eloquent\\Fields\\Number;
use LaravelJsonApi\\Eloquent\\Fields\\Boolean;
use LaravelJsonApi\\Eloquent\\Fields\\ArrayHash;
use LaravelJsonApi\\Eloquent\\Relations\\BelongsTo;
use LaravelJsonApi\\Eloquent\\Relations\\HasMany;
use LaravelJsonApi\\Eloquent\\Filters\\WhereIdIn;
use LaravelJsonApi\\Eloquent\\Pagination\\PagePagination;
use LaravelJsonApi\\Eloquent\\Schema;
use Modules\\{$moduleName}\\Models\\{$entityName};

class {$entityName}Schema extends Schema
{
    public static string \$model = {$entityName}::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
{$fieldsCode}
            
            // Metadatos
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make(\$this),
{$filtersCode}
        ];
    }

    public function sortables(): array
    {
        return [
{$sortablesCode}
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [
            // Add relationships here when needed
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return \"{$resourceType}\";
    }
}";
    }

    /**
     * Generate schema fields code
     */
    private function generateSchemaFields(array $fields): string
    {
        $lines = [];
        
        foreach ($fields as $field) {
            $fieldLine = $this->generateSchemaFieldLine($field);
            if ($fieldLine) {
                $lines[] = "            {$fieldLine}";
            }
        }
        
        return implode("\n", $lines);
    }

    /**
     * Generate individual schema field line
     */
    private function generateSchemaFieldLine(array $field): string
    {
        $schemaType = $this->mapToSchemaType($field['type']);
        $camelFieldName = Str::camel($field['name']);
        
        $line = "{$schemaType}::make('{$camelFieldName}')";
        
        // Add sortable if appropriate
        if (in_array($field['type'], ['string', 'integer', 'bigInteger', 'decimal', 'boolean', 'date', 'datetime'])) {
            $line .= "->sortable()";
        }
        
        $line .= ",";
        
        return $line;
    }

    /**
     * Map field type to JSON:API schema type
     */
    private function mapToSchemaType(string $fieldType): string
    {
        $typeMap = [
            'string' => 'Str',
            'text' => 'Str', 
            'integer' => 'Number',
            'bigInteger' => 'Number',
            'decimal' => 'Number',
            'boolean' => 'Boolean',
            'date' => 'DateTime',
            'datetime' => 'DateTime',
            'timestamp' => 'DateTime',
            'json' => 'ArrayHash',
            'foreignId' => 'Number'
        ];
        
        return $typeMap[$fieldType] ?? 'Str';
    }

    /**
     * Generate schema filters
     */
    private function generateSchemaFilters(array $fields): string
    {
        $lines = [];
        
        foreach ($fields as $field) {
            if (in_array($field['type'], ['string', 'boolean', 'integer', 'bigInteger'])) {
                $lines[] = "            \\LaravelJsonApi\\Eloquent\\Filters\\Where::make('{$field['name']}'),";
            }
        }
        
        return implode("\n", $lines);
    }

    /**
     * Generate schema sortables
     */
    private function generateSchemaSortables(array $fields): string
    {
        $lines = [];
        
        foreach ($fields as $field) {
            if (in_array($field['type'], ['string', 'integer', 'bigInteger', 'decimal', 'boolean', 'date', 'datetime'])) {
                $lines[] = "            '{$field['name']}',";
            }
        }
        
        return implode("\n", $lines);
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