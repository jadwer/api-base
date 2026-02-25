<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use App\Console\Commands\ModuleGeneration\NamingHelper;
use Illuminate\Support\Facades\File;

class RouteGenerator
{
    public function generate(ModuleConfig $module): string
    {
        $path = base_path("Modules/{$module->name}/routes/jsonapi.php");
        File::ensureDirectoryExists(dirname($path));

        $content = $this->buildContent($module);
        File::put($path, $content);

        return $path;
    }

    private function buildContent(ModuleConfig $module): string
    {
        $imports = $this->buildImports($module);
        $resources = $this->buildResources($module);

        return <<<PHP
<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
{$imports}

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar \$server) {
{$resources}
    });

PHP;
    }

    private function buildImports(ModuleConfig $module): string
    {
        $lines = [];
        foreach ($module->entities as $entity) {
            $lines[] = "use Modules\\{$module->name}\\Http\\Controllers\\Api\\V1\\{$entity->modelName}Controller;";
        }
        return implode("\n", $lines);
    }

    private function buildResources(ModuleConfig $module): string
    {
        $blocks = [];

        foreach ($module->entities as $entity) {
            $rels = $this->buildRelationshipRoutes($entity);
            if ($rels) {
                $blocks[] = <<<PHP
        \$server->resource('{$entity->jsonApiType}', {$entity->modelName}Controller::class)
            ->relationships(function (\$relationships) {
{$rels}
            });
PHP;
            } else {
                $blocks[] = "        \$server->resource('{$entity->jsonApiType}', {$entity->modelName}Controller::class);";
            }
        }

        return implode("\n", $blocks);
    }

    private function buildRelationshipRoutes(EntityConfig $entity): string
    {
        $lines = [];

        foreach ($entity->getBelongsToRelationships() as $rel) {
            $name = NamingHelper::toRelationMethod($rel['model']);
            $lines[] = "                \$relationships->hasOne('{$name}');";
        }
        foreach ($entity->getHasManyRelationships() as $rel) {
            $name = isset($rel['entity'])
                ? NamingHelper::toRelationMethod(NamingHelper::toPlural($rel['entity']))
                : lcfirst(NamingHelper::toPlural($rel['model']));
            $lines[] = "                \$relationships->hasMany('{$name}');";
        }
        foreach ($entity->getHasOneRelationships() as $rel) {
            $name = NamingHelper::toRelationMethod($rel['model']);
            $lines[] = "                \$relationships->hasOne('{$name}');";
        }

        return implode("\n", $lines);
    }
}
