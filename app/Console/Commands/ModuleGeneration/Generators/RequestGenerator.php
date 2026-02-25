<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\Contracts\GeneratorInterface;
use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use Illuminate\Support\Facades\File;

class RequestGenerator implements GeneratorInterface
{
    public function generate(ModuleConfig $module, EntityConfig $entity): string
    {
        $dir = "Modules/{$module->name}/app/JsonApi/V1/{$entity->pluralName}";
        $path = base_path("{$dir}/{$entity->modelName}Request.php");
        File::ensureDirectoryExists(dirname($path));

        $content = $this->buildContent($module, $entity);
        File::put($path, $content);

        return $path;
    }

    private function buildContent(ModuleConfig $module, EntityConfig $entity): string
    {
        $namespace = "Modules\\{$module->name}\\JsonApi\\V1\\{$entity->pluralName}";
        $hasUnique = $this->hasUniqueFields($entity);
        $hasInRule = $this->hasInRule($entity);
        $hasToOneRels = !empty($entity->getBelongsToRelationships());

        $imports = $this->buildImports($hasUnique, $hasInRule, $hasToOneRels);
        $rules = $this->buildRules($entity);
        $defaults = $this->buildDefaults($entity);

        return <<<PHP
<?php

namespace {$namespace};

{$imports}

class {$entity->modelName}Request extends ResourceRequest
{
    public function rules(): array
    {
        \$model = \$this->model();

{$rules}
    }
{$defaults}}

PHP;
    }

    private function buildImports(bool $hasUnique, bool $hasInRule, bool $hasToOneRels): string
    {
        $lines = ['use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;'];

        if ($hasUnique || $hasInRule) {
            $lines[] = 'use Illuminate\Validation\Rule;';
        }
        if ($hasToOneRels) {
            $lines[] = 'use LaravelJsonApi\Validation\Rule as JsonApiRule;';
        }

        sort($lines);
        return implode("\n", $lines);
    }

    private function buildRules(EntityConfig $entity): string
    {
        $lines = [];
        $lines[] = '        return [';

        foreach ($entity->fields as $field) {
            $createRules = $field->getValidationRules($entity->tableName, false);
            $updateRules = $field->getValidationRules($entity->tableName, true);

            if ($createRules === $updateRules) {
                $lines[] = "            '{$field->apiName}' => {$createRules},";
            } else {
                $lines[] = "            '{$field->apiName}' => \$model !== null ? {$updateRules} : {$createRules},";
            }
        }

        // BelongsTo relationship rules
        foreach ($entity->getBelongsToRelationships() as $rel) {
            $relName = lcfirst($rel['model']);
            $nullable = ($rel['nullable'] ?? false);
            if ($nullable) {
                $lines[] = "            '{$relName}' => JsonApiRule::toOne(),";
            } else {
                $lines[] = "            '{$relName}' => [\$model !== null ? 'sometimes' : 'required', JsonApiRule::toOne()],";
            }
        }

        $lines[] = '        ];';

        return implode("\n", $lines);
    }

    private function buildDefaults(EntityConfig $entity): string
    {
        $fieldsWithDefaults = $entity->getFieldsWithDefaults();
        if (empty($fieldsWithDefaults)) {
            return '';
        }

        $lines = [];
        $lines[] = '';
        $lines[] = '    public function withDefaults(): array';
        $lines[] = '    {';
        $lines[] = '        return [';

        foreach ($fieldsWithDefaults as $field) {
            $value = $this->formatDefaultValue($field->default);
            $lines[] = "            '{$field->apiName}' => {$value},";
        }

        $lines[] = '        ];';
        $lines[] = '    }';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function hasUniqueFields(EntityConfig $entity): bool
    {
        foreach ($entity->fields as $field) {
            if ($field->isUnique) return true;

            // Also check for 'in:' rules which use Rule::in()
            if (preg_match('/in:/', $field->rules)) return true;
        }
        return false;
    }

    private function hasInRule(EntityConfig $entity): bool
    {
        foreach ($entity->fields as $field) {
            if (preg_match('/in:/', $field->rules)) return true;
        }
        return false;
    }

    private function formatDefaultValue(mixed $value): string
    {
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_string($value)) return "'{$value}'";
        if (is_null($value)) return 'null';
        return (string) $value;
    }
}
