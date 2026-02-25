<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\Contracts\GeneratorInterface;
use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use App\Console\Commands\ModuleGeneration\NamingHelper;
use Illuminate\Support\Facades\File;

class SchemaGenerator implements GeneratorInterface
{
    public function generate(ModuleConfig $module, EntityConfig $entity): string
    {
        $dir = "Modules/{$module->name}/app/JsonApi/V1/{$entity->pluralName}";
        $path = base_path("{$dir}/{$entity->modelName}Schema.php");
        File::ensureDirectoryExists(dirname($path));

        $content = $this->buildContent($module, $entity);
        File::put($path, $content);

        return $path;
    }

    private function buildContent(ModuleConfig $module, EntityConfig $entity): string
    {
        $namespace = "Modules\\{$module->name}\\JsonApi\\V1\\{$entity->pluralName}";
        $imports = $this->buildImports($module, $entity);
        $fields = $this->buildFields($entity);
        $filters = $this->buildFilters($entity);
        $includePaths = $this->buildIncludePaths($entity);

        return <<<PHP
<?php

namespace {$namespace};

{$imports}

class {$entity->modelName}Schema extends Schema
{
    public static string \$model = {$entity->modelName}::class;

    public static function type(): string
    {
        return '{$entity->jsonApiType}';
    }

    public function fields(): array
    {
        return [
{$fields}
        ];
    }

    public function filters(): array
    {
        return [
{$filters}
        ];
    }

    public function includePaths(): array
    {
        return [
{$includePaths}
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}

PHP;
    }

    private function buildImports(ModuleConfig $module, EntityConfig $entity): string
    {
        $lines = [
            'use LaravelJsonApi\Eloquent\Schema;',
            'use LaravelJsonApi\Eloquent\Fields\ID;',
            'use LaravelJsonApi\Eloquent\Fields\DateTime;',
            'use LaravelJsonApi\Eloquent\Filters\WhereIdIn;',
            'use LaravelJsonApi\Eloquent\Pagination\PagePagination;',
            'use LaravelJsonApi\Eloquent\Contracts\Paginator;',
            "use Modules\\{$module->name}\\Models\\{$entity->modelName};",
        ];

        // Collect required field type imports
        $fieldClasses = [];
        foreach ($entity->fields as $field) {
            $class = $this->getFieldImportClass($field->type);
            if ($class) {
                $fieldClasses[$class] = true;
            }
        }
        foreach (array_keys($fieldClasses) as $class) {
            $lines[] = "use LaravelJsonApi\\Eloquent\\Fields\\{$class};";
        }

        // Filter imports
        $hasFilters = false;
        foreach ($entity->fields as $field) {
            if ($field->isFilterable) {
                $hasFilters = true;
                break;
            }
        }
        if ($hasFilters) {
            $lines[] = 'use LaravelJsonApi\Eloquent\Filters\Where;';
        }

        // Relationship imports
        $hasBelongsTo = !empty($entity->getBelongsToRelationships());
        $hasHasMany = !empty($entity->getHasManyRelationships());
        $hasHasOne = !empty($entity->getHasOneRelationships());

        if ($hasBelongsTo) {
            $lines[] = 'use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;';
        }
        if ($hasHasMany) {
            $lines[] = 'use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;';
        }
        if ($hasHasOne) {
            $lines[] = 'use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;';
        }

        sort($lines);
        return implode("\n", $lines);
    }

    private function buildFields(EntityConfig $entity): string
    {
        $lines = [];
        $lines[] = '            ID::make(),';

        foreach ($entity->fields as $field) {
            $lines[] = "            {$field->getSchemaField()},";
        }

        // Relationship fields
        foreach ($entity->getBelongsToRelationships() as $rel) {
            $relName = NamingHelper::toRelationMethod($rel['model']);
            $type = NamingHelper::toJsonApiType($rel['model']);
            $lines[] = "            BelongsTo::make('{$relName}')->type('{$type}'),";
        }
        foreach ($entity->getHasManyRelationships() as $rel) {
            $relName = isset($rel['entity'])
                ? NamingHelper::toRelationMethod(NamingHelper::toPlural($rel['entity']))
                : lcfirst(NamingHelper::toPlural($rel['model']));
            $type = isset($rel['entity'])
                ? NamingHelper::toJsonApiType($rel['entity'])
                : NamingHelper::toJsonApiType($rel['model']);
            $lines[] = "            HasMany::make('{$relName}')->type('{$type}'),";
        }
        foreach ($entity->getHasOneRelationships() as $rel) {
            $relName = NamingHelper::toRelationMethod($rel['model']);
            $type = NamingHelper::toJsonApiType($rel['model']);
            $lines[] = "            HasOne::make('{$relName}')->type('{$type}'),";
        }

        // Timestamps
        $lines[] = "            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),";
        $lines[] = "            DateTime::make('updatedAt', 'updated_at')->readOnly(),";

        return implode("\n", $lines);
    }

    private function buildFilters(EntityConfig $entity): string
    {
        $lines = [];
        $lines[] = '            WhereIdIn::make($this),';

        foreach ($entity->fields as $field) {
            if (!$field->isFilterable) continue;

            $needsMapping = $field->dbName !== $field->apiName;
            $args = $needsMapping
                ? "'{$field->apiName}', '{$field->dbName}'"
                : "'{$field->apiName}'";

            $chain = "Where::make({$args})";
            if ($field->type === 'boolean') {
                $chain .= '->asBoolean()';
            }

            $lines[] = "            {$chain},";
        }

        return implode("\n", $lines);
    }

    private function buildIncludePaths(EntityConfig $entity): string
    {
        $paths = [];
        foreach ($entity->getBelongsToRelationships() as $rel) {
            $paths[] = NamingHelper::toRelationMethod($rel['model']);
        }
        foreach ($entity->getHasManyRelationships() as $rel) {
            $paths[] = isset($rel['entity'])
                ? NamingHelper::toRelationMethod(NamingHelper::toPlural($rel['entity']))
                : lcfirst(NamingHelper::toPlural($rel['model']));
        }
        foreach ($entity->getHasOneRelationships() as $rel) {
            $paths[] = NamingHelper::toRelationMethod($rel['model']);
        }

        $lines = [];
        foreach ($paths as $path) {
            $lines[] = "            '{$path}',";
        }
        return implode("\n", $lines);
    }

    private function getFieldImportClass(string $type): ?string
    {
        return match ($type) {
            'string', 'text', 'enum' => 'Str',
            'integer', 'bigInteger', 'decimal' => 'Number',
            'boolean' => 'Boolean',
            'json' => 'ArrayHash',
            // DateTime is already imported for timestamps
            'datetime', 'date' => null,
            default => 'Str',
        };
    }
}
