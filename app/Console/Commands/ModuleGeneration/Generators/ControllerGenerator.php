<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\Contracts\GeneratorInterface;
use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use Illuminate\Support\Facades\File;

class ControllerGenerator implements GeneratorInterface
{
    public function generate(ModuleConfig $module, EntityConfig $entity): string
    {
        $dir = "Modules/{$module->name}/app/Http/Controllers/Api/V1";
        $path = base_path("{$dir}/{$entity->modelName}Controller.php");
        File::ensureDirectoryExists(dirname($path));

        $namespace = "Modules\\{$module->name}\\Http\\Controllers\\Api\\V1";

        $content = <<<PHP
<?php

namespace {$namespace};

use App\Http\Controllers\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class {$entity->modelName}Controller extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;
}

PHP;

        File::put($path, $content);
        return $path;
    }
}
