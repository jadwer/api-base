<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\Contracts\GeneratorInterface;
use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use Illuminate\Support\Facades\File;

class AuthorizerGenerator implements GeneratorInterface
{
    public function generate(ModuleConfig $module, EntityConfig $entity): string
    {
        $dir = "Modules/{$module->name}/app/JsonApi/V1/{$entity->pluralName}";
        $path = base_path("{$dir}/{$entity->modelName}Authorizer.php");
        File::ensureDirectoryExists(dirname($path));

        $content = $this->buildContent($module, $entity);
        File::put($path, $content);

        return $path;
    }

    private function buildContent(ModuleConfig $module, EntityConfig $entity): string
    {
        $namespace = "Modules\\{$module->name}\\JsonApi\\V1\\{$entity->pluralName}";
        $perm = $entity->permissionPrefix;

        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class {$entity->modelName}Authorizer implements Authorizer
{
    public function index(Request \$request, string \$modelClass): bool|Response
    {
        return \$request->user()?->can('{$perm}.index') ?? false;
    }

    public function store(Request \$request, string \$modelClass): bool|Response
    {
        return \$request->user()?->can('{$perm}.store') ?? false;
    }

    public function show(Request \$request, object \$model): bool|Response
    {
        return \$request->user()?->can('{$perm}.show') ?? false;
    }

    public function update(Request \$request, object \$model): bool|Response
    {
        return \$request->user()?->can('{$perm}.update') ?? false;
    }

    public function destroy(Request \$request, object \$model): bool|Response
    {
        return \$request->user()?->can('{$perm}.destroy') ?? false;
    }

    public function showRelated(Request \$request, object \$model, string \$fieldName): bool|Response
    {
        return \$request->user()?->can('{$perm}.show') ?? false;
    }

    public function showRelationship(Request \$request, object \$model, string \$fieldName): bool|Response
    {
        return \$request->user()?->can('{$perm}.show') ?? false;
    }

    public function updateRelationship(Request \$request, object \$model, string \$fieldName): bool|Response
    {
        return \$request->user()?->can('{$perm}.update') ?? false;
    }

    public function attachRelationship(Request \$request, object \$model, string \$fieldName): bool|Response
    {
        return \$request->user()?->can('{$perm}.update') ?? false;
    }

    public function detachRelationship(Request \$request, object \$model, string \$fieldName): bool|Response
    {
        return \$request->user()?->can('{$perm}.update') ?? false;
    }
}

PHP;
    }
}
