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
        $relationshipsCode = $this->generateSchemaRelationships($entityName, $relationships);
        $includePathsCode = $this->generateIncludePaths($entityName, $relationships);
        $filtersCode = $this->generateSchemaFilters($fields);
        $sortablesCode = $this->generateSchemaSortables($fields);
        $resourceType = Str::kebab(Str::plural($entityName));
        $metadataCode = $this->shouldAddMetadataField($fields) ? "\n            // Metadata\n            ArrayHash::make('metadata')," : "";
        
        return "<?php

namespace {$namespace};

use LaravelJsonApi\\Eloquent\\Contracts\\Paginator;
use LaravelJsonApi\\Eloquent\\Fields\\DateTime;
use LaravelJsonApi\\Eloquent\\Fields\\ID;
use LaravelJsonApi\\Eloquent\\Fields\\Str;
use LaravelJsonApi\\Eloquent\\Fields\\Number;
use LaravelJsonApi\\Eloquent\\Fields\\Boolean;
use LaravelJsonApi\\Eloquent\\Fields\\ArrayHash;
use LaravelJsonApi\\Eloquent\\Fields\\Relations\\BelongsTo;
use LaravelJsonApi\\Eloquent\\Fields\\Relations\\HasMany;
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
            
{$fieldsCode}{$metadataCode}
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
{$relationshipsCode}
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
{$includePathsCode}
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
     * Generate schema relationships
     */
    private function generateSchemaRelationships(string $entityName, array $relationships): string
    {
        $lines = [];
        
        foreach ($relationships as $rel) {
            // Only generate relationships where current entity is the 'from' (owner)
            $fromEntity = $rel['from'] ?? $rel['entityA'] ?? null;
            
            if ($fromEntity === $entityName) {
                $relatedEntity = $rel['to'] ?? $rel['entityB'] ?? null;
                $relationType = $rel['type'];
                
                if ($relationType === 'hasMany') {
                    $methodName = Str::camel(Str::plural($relatedEntity));
                    $lines[] = "\n            // Relationships\n            HasMany::make('{$methodName}'),";
                } elseif ($relationType === 'belongsTo') {
                    $methodName = Str::camel($relatedEntity);
                    $lines[] = "\n            // Relationships\n            BelongsTo::make('{$methodName}'),";
                }
            }
        }
        
        return implode("", array_unique($lines)); // Remove duplicates
    }

    /**
     * Generate include paths for relationships
     */
    private function generateIncludePaths(string $entityName, array $relationships): string
    {
        $paths = [];
        
        foreach ($relationships as $rel) {
            // Only generate paths where current entity is the 'from' (owner)
            $fromEntity = $rel['from'] ?? $rel['entityA'] ?? null;
            
            if ($fromEntity === $entityName) {
                $relatedEntity = $rel['to'] ?? $rel['entityB'] ?? null;
                $relationType = $rel['type'];
                
                if ($relationType === 'hasMany') {
                    $methodName = Str::camel(Str::plural($relatedEntity));
                    $paths[] = "            '{$methodName}',";
                } elseif ($relationType === 'belongsTo') {
                    $methodName = Str::camel($relatedEntity);
                    $paths[] = "            '{$methodName}',";
                }
            }
        }
        
        return implode("\n", array_unique($paths));
    }

    /**
     * Check if metadata field should be added
     */
    private function shouldAddMetadataField(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field['name'] === 'metadata') {
                return false; // Don't add duplicate metadata field
            }
        }
        return true; // Add metadata field if not explicitly defined
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