<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\Contracts\GeneratorInterface;
use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use App\Console\Commands\ModuleGeneration\NamingHelper;
use Illuminate\Support\Facades\File;

class FactoryGenerator implements GeneratorInterface
{
    public function generate(ModuleConfig $module, EntityConfig $entity): string
    {
        $path = base_path("Modules/{$module->name}/Database/factories/{$entity->modelName}Factory.php");
        File::ensureDirectoryExists(dirname($path));

        $content = $this->buildContent($module, $entity);
        File::put($path, $content);

        return $path;
    }

    private function buildContent(ModuleConfig $module, EntityConfig $entity): string
    {
        $namespace = "Modules\\{$module->name}\\Database\\Factories";
        $imports = $this->buildImports($module, $entity);
        $definition = $this->buildDefinition($module, $entity);

        return <<<PHP
<?php

namespace {$namespace};

{$imports}

class {$entity->modelName}Factory extends Factory
{
    protected \$model = {$entity->modelName}::class;

    public function definition(): array
    {
        return [
{$definition}
        ];
    }
}

PHP;
    }

    private function buildImports(ModuleConfig $module, EntityConfig $entity): string
    {
        $lines = [
            "use Modules\\{$module->name}\\Models\\{$entity->modelName};",
            'use Illuminate\Database\Eloquent\Factories\Factory;',
        ];

        // Import related models for foreign keys
        foreach ($entity->getBelongsToRelationships() as $rel) {
            if (isset($rel['module'])) {
                $lines[] = "use Modules\\{$rel['module']}\\Models\\{$rel['model']};";
            } else {
                $lines[] = "use Modules\\{$module->name}\\Models\\{$rel['model']};";
            }
        }

        sort($lines);
        return implode("\n", $lines);
    }

    private function buildDefinition(ModuleConfig $module, EntityConfig $entity): string
    {
        $lines = [];

        foreach ($entity->fields as $field) {
            $value = $field->getFakerExpression();

            // Override if nullable: wrap in boolean check
            if ($field->isNullable && !$field->isRequired) {
                $value = "\$this->faker->boolean(70) ? {$value} : null";
            }

            // Use default value for booleans/enums with defaults
            if ($field->hasDefault && $field->type === 'boolean') {
                $value = $field->default ? 'true' : 'false';
            }

            $lines[] = "            '{$field->dbName}' => {$value},";
        }

        // Foreign key values
        foreach ($entity->getBelongsToRelationships() as $rel) {
            $fk = NamingHelper::toForeignKey($rel['model']);
            $lines[] = "            '{$fk}' => {$rel['model']}::factory(),";
        }

        return implode("\n", $lines);
    }
}
