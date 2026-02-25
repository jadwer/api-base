<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\Contracts\GeneratorInterface;
use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use App\Console\Commands\ModuleGeneration\NamingHelper;
use Illuminate\Support\Facades\File;

class ModelGenerator implements GeneratorInterface
{
    public function generate(ModuleConfig $module, EntityConfig $entity): string
    {
        $path = base_path("Modules/{$module->name}/app/Models/{$entity->modelName}.php");
        File::ensureDirectoryExists(dirname($path));

        $content = $this->buildContent($module, $entity);
        File::put($path, $content);

        return $path;
    }

    private function buildContent(ModuleConfig $module, EntityConfig $entity): string
    {
        $namespace = "Modules\\{$module->name}\\Models";
        $imports = $this->buildImports($module, $entity);
        $fillable = $this->buildFillable($entity);
        $casts = $this->buildCasts($entity);
        $relationships = $this->buildRelationships($module, $entity);

        return <<<PHP
<?php

namespace {$namespace};

{$imports}

class {$entity->modelName} extends Model
{
    use HasFactory, LogsActivity;

    protected \$table = '{$entity->tableName}';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(\$this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected \$fillable = [
{$fillable}
    ];

    protected \$casts = [
{$casts}
    ];
{$relationships}
    protected static function newFactory()
    {
        return \\Modules\\{$module->name}\\Database\\Factories\\{$entity->modelName}Factory::new();
    }
}

PHP;
    }

    private function buildImports(ModuleConfig $module, EntityConfig $entity): string
    {
        $lines = [
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use Illuminate\Database\Eloquent\Model;',
        ];

        // Relationship type imports
        $hasRelTypes = [];
        foreach ($entity->relationships as $rel) {
            $hasRelTypes[$rel['type']] = true;
        }
        foreach (['BelongsTo', 'HasMany', 'HasOne'] as $type) {
            $lcType = lcfirst($type);
            if (isset($hasRelTypes[$lcType])) {
                $lines[] = "use Illuminate\\Database\\Eloquent\\Relations\\{$type};";
            }
        }

        $lines[] = 'use Spatie\Activitylog\Traits\LogsActivity;';
        $lines[] = 'use Spatie\Activitylog\LogOptions;';

        sort($lines);
        return implode("\n", $lines);
    }

    private function buildFillable(EntityConfig $entity): string
    {
        $items = [];
        foreach ($entity->fields as $field) {
            $items[] = "        '{$field->dbName}',";
        }
        foreach ($entity->getBelongsToRelationships() as $rel) {
            $fk = NamingHelper::toForeignKey($rel['model']);
            $items[] = "        '{$fk}',";
        }
        return implode("\n", $items);
    }

    private function buildCasts(EntityConfig $entity): string
    {
        $casts = $entity->getCasts();
        $lines = [];
        foreach ($casts as $column => $type) {
            $lines[] = "        '{$column}' => {$type},";
        }
        return implode("\n", $lines);
    }

    private function buildRelationships(ModuleConfig $module, EntityConfig $entity): string
    {
        $methods = [];

        foreach ($entity->getBelongsToRelationships() as $rel) {
            $methodName = NamingHelper::toRelationMethod($rel['model']);
            $modelClass = $this->resolveModelClass($module, $rel);
            $methods[] = <<<PHP

    public function {$methodName}(): BelongsTo
    {
        return \$this->belongsTo({$modelClass});
    }
PHP;
        }

        foreach ($entity->getHasManyRelationships() as $rel) {
            $methodName = lcfirst(NamingHelper::toPlural($rel['model']));
            if (isset($rel['entity'])) {
                $methodName = NamingHelper::toRelationMethod(NamingHelper::toPlural($rel['entity']));
            }
            $modelClass = $this->resolveModelClass($module, $rel);
            $methods[] = <<<PHP

    public function {$methodName}(): HasMany
    {
        return \$this->hasMany({$modelClass});
    }
PHP;
        }

        foreach ($entity->getHasOneRelationships() as $rel) {
            $methodName = NamingHelper::toRelationMethod($rel['model']);
            $modelClass = $this->resolveModelClass($module, $rel);
            $methods[] = <<<PHP

    public function {$methodName}(): HasOne
    {
        return \$this->hasOne({$modelClass});
    }
PHP;
        }

        if (!empty($methods)) {
            $methods[] = '';
        }

        // Add TODO for business logic
        $methods[] = <<<PHP

    // TODO: Add business scopes and methods here
PHP;
        $methods[] = '';

        return implode("\n", $methods);
    }

    private function resolveModelClass(ModuleConfig $module, array $rel): string
    {
        if (isset($rel['module'])) {
            // Cross-module: use FQN
            return "\\Modules\\{$rel['module']}\\Models\\{$rel['model']}::class";
        }
        // Same module: short name
        return "{$rel['model']}::class";
    }
}
