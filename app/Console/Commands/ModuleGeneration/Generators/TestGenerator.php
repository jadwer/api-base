<?php

namespace App\Console\Commands\ModuleGeneration\Generators;

use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;
use App\Console\Commands\ModuleGeneration\NamingHelper;
use Illuminate\Support\Facades\File;

class TestGenerator
{
    /**
     * Generate all 5 test files for an entity
     * @return string[] Generated file paths
     */
    public function generate(ModuleConfig $module, EntityConfig $entity): array
    {
        $dir = base_path("Modules/{$module->name}/tests/Feature");
        File::ensureDirectoryExists($dir);

        return [
            $this->generateIndex($module, $entity, $dir),
            $this->generateShow($module, $entity, $dir),
            $this->generateStore($module, $entity, $dir),
            $this->generateUpdate($module, $entity, $dir),
            $this->generateDestroy($module, $entity, $dir),
        ];
    }

    // ===== Index Test =====

    private function generateIndex(ModuleConfig $module, EntityConfig $entity, string $dir): string
    {
        $path = "{$dir}/{$entity->modelName}IndexTest.php";
        $namespace = "Modules\\{$module->name}\\Tests\\Feature";
        $modelImport = "Modules\\{$module->name}\\Models\\{$entity->modelName}";
        $type = $entity->jsonApiType;
        $model = $entity->modelName;
        $sortField = $entity->getFirstSortableField();
        $filterField = $entity->getFirstFilterableField();

        $sortTest = '';
        if ($sortField) {
            $sortTest = <<<PHP


    public function test_admin_can_sort_{$model}s_by_{$sortField->dbName}(): void
    {
        \$admin = \$this->getAdminUser();

        {$model}::factory()->count(2)->create();

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->get('/api/v1/{$type}?sort={$sortField->apiName}');

        \$response->assertOk();
    }
PHP;
        }

        $filterTest = '';
        if ($filterField) {
            $filterValue = $this->getFilterTestValue($filterField);
            $filterTest = <<<PHP


    public function test_admin_can_filter_{$model}s_by_{$filterField->dbName}(): void
    {
        \$admin = \$this->getAdminUser();

        {$model}::factory()->create(['{$filterField->dbName}' => {$filterValue}]);

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->get('/api/v1/{$type}?filter[{$filterField->apiName}]={$this->getFilterQueryValue($filterField)}');

        \$response->assertOk();
    }
PHP;
        }

        $content = <<<PHP
<?php

namespace {$namespace};

use Tests\TestCase;
use {$modelImport};

class {$model}IndexTest extends TestCase
{
    public function test_admin_can_list_{$model}s(): void
    {
        \$admin = \$this->getAdminUser();

        {$model}::factory()->count(3)->create();

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->get('/api/v1/{$type}');

        \$response->assertOk();
    }
{$sortTest}
{$filterTest}

    public function test_tech_user_can_list_{$model}s_with_permission(): void
    {
        \$tech = \$this->getTechUser();

        \$response = \$this->actingAs(\$tech, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->get('/api/v1/{$type}');

        \$response->assertOk();
    }

    public function test_customer_user_can_list_{$model}s_read_only(): void
    {
        \$customer = \$this->getCustomerUser();

        \$response = \$this->actingAs(\$customer, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->get('/api/v1/{$type}');

        \$response->assertOk();
    }

    public function test_guest_cannot_list_{$model}s(): void
    {
        \$response = \$this->jsonApi()
            ->expects('{$type}')
            ->get('/api/v1/{$type}');

        \$response->assertStatus(401);
    }

    public function test_can_paginate_{$model}s(): void
    {
        \$admin = \$this->getAdminUser();

        {$model}::factory()->count(25)->create();

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->get('/api/v1/{$type}?page[size]=10');

        \$response->assertOk();
        \$this->assertCount(10, \$response->json('data'));
        \$response->assertJsonStructure(['links', 'meta']);
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }

    // ===== Show Test =====

    private function generateShow(ModuleConfig $module, EntityConfig $entity, string $dir): string
    {
        $path = "{$dir}/{$entity->modelName}ShowTest.php";
        $namespace = "Modules\\{$module->name}\\Tests\\Feature";
        $modelImport = "Modules\\{$module->name}\\Models\\{$entity->modelName}";
        $type = $entity->jsonApiType;
        $model = $entity->modelName;
        $lcModel = lcfirst($model);

        $content = <<<PHP
<?php

namespace {$namespace};

use Tests\TestCase;
use {$modelImport};

class {$model}ShowTest extends TestCase
{
    public function test_admin_can_view_{$model}(): void
    {
        \$admin = \$this->getAdminUser();
        \${$lcModel} = {$model}::factory()->create();

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->get("/api/v1/{$type}/{\${$lcModel}->id}");

        \$response->assertOk();
    }

    public function test_guest_cannot_view_{$model}(): void
    {
        \${$lcModel} = {$model}::factory()->create();

        \$response = \$this->jsonApi()
            ->expects('{$type}')
            ->get("/api/v1/{$type}/{\${$lcModel}->id}");

        \$response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_{$model}(): void
    {
        \$admin = \$this->getAdminUser();

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->get('/api/v1/{$type}/99999');

        \$response->assertStatus(404);
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }

    // ===== Store Test =====

    private function generateStore(ModuleConfig $module, EntityConfig $entity, string $dir): string
    {
        $path = "{$dir}/{$entity->modelName}StoreTest.php";
        $namespace = "Modules\\{$module->name}\\Tests\\Feature";
        $modelImport = "Modules\\{$module->name}\\Models\\{$entity->modelName}";
        $type = $entity->jsonApiType;
        $model = $entity->modelName;

        $fullData = $this->buildStoreData($entity, false);
        $minimalData = $this->buildStoreData($entity, true);
        $dbAssert = $this->buildDatabaseAssert($entity);
        $relImports = $this->buildRelationshipImports($module, $entity);
        $relSetup = $this->buildRelationshipSetup($entity);
        $relData = $this->buildRelationshipData($entity);

        $content = <<<PHP
<?php

namespace {$namespace};

use Tests\TestCase;
use {$modelImport};
{$relImports}

class {$model}StoreTest extends TestCase
{
    public function test_admin_can_create_{$model}(): void
    {
        \$admin = \$this->getAdminUser();
{$relSetup}
        \$data = [
            'type' => '{$type}',
            'attributes' => [
{$fullData}
            ],
{$relData}
        ];

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->withData(\$data)
            ->post('/api/v1/{$type}');

        \$response->assertCreated();

        \$this->assertDatabaseHas('{$entity->tableName}', [{$dbAssert}]);
    }

    public function test_customer_user_cannot_create_{$model}(): void
    {
        \$customer = \$this->getCustomerUser();
{$relSetup}
        \$data = [
            'type' => '{$type}',
            'attributes' => [
{$minimalData}
            ],
{$relData}
        ];

        \$response = \$this->actingAs(\$customer, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->withData(\$data)
            ->post('/api/v1/{$type}');

        \$response->assertStatus(403);
    }

    public function test_guest_cannot_create_{$model}(): void
    {
        \$data = [
            'type' => '{$type}',
            'attributes' => [
{$minimalData}
            ],
        ];

        \$response = \$this->jsonApi()
            ->expects('{$type}')
            ->withData(\$data)
            ->post('/api/v1/{$type}');

        \$response->assertStatus(401);
    }

    public function test_cannot_create_{$model}_without_required_fields(): void
    {
        \$admin = \$this->getAdminUser();

        \$data = [
            'type' => '{$type}',
            'attributes' => (object) [],
        ];

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->withData(\$data)
            ->post('/api/v1/{$type}');

        \$response->assertStatus(422);
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }

    // ===== Update Test =====

    private function generateUpdate(ModuleConfig $module, EntityConfig $entity, string $dir): string
    {
        $path = "{$dir}/{$entity->modelName}UpdateTest.php";
        $namespace = "Modules\\{$module->name}\\Tests\\Feature";
        $modelImport = "Modules\\{$module->name}\\Models\\{$entity->modelName}";
        $type = $entity->jsonApiType;
        $model = $entity->modelName;
        $lcModel = lcfirst($model);

        $firstField = $entity->fields[0] ?? null;
        $updateAttrLine = '';
        $updateAssertLine = '';
        if ($firstField) {
            $testVal = $firstField->getTestValue();
            $updateAttrLine = "                '{$firstField->apiName}' => {$testVal},";
            $updateAssertLine = "'{$firstField->dbName}' => {$testVal}";
        }

        $content = <<<PHP
<?php

namespace {$namespace};

use Tests\TestCase;
use {$modelImport};

class {$model}UpdateTest extends TestCase
{
    public function test_admin_can_update_{$model}(): void
    {
        \$admin = \$this->getAdminUser();
        \${$lcModel} = {$model}::factory()->create();

        \$data = [
            'type' => '{$type}',
            'id' => (string) \${$lcModel}->id,
            'attributes' => [
{$updateAttrLine}
            ]
        ];

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->withData(\$data)
            ->patch("/api/v1/{$type}/{\${$lcModel}->id}");

        \$response->assertOk();
        \$this->assertDatabaseHas('{$entity->tableName}', [{$updateAssertLine}]);
    }

    public function test_guest_cannot_update_{$model}(): void
    {
        \${$lcModel} = {$model}::factory()->create();

        \$data = [
            'type' => '{$type}',
            'id' => (string) \${$lcModel}->id,
            'attributes' => [
{$updateAttrLine}
            ]
        ];

        \$response = \$this->jsonApi()
            ->expects('{$type}')
            ->withData(\$data)
            ->patch("/api/v1/{$type}/{\${$lcModel}->id}");

        \$response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_{$model}(): void
    {
        \$admin = \$this->getAdminUser();

        \$data = [
            'type' => '{$type}',
            'id' => '99999',
            'attributes' => [
{$updateAttrLine}
            ]
        ];

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->withData(\$data)
            ->patch('/api/v1/{$type}/99999');

        \$response->assertStatus(404);
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }

    // ===== Destroy Test =====

    private function generateDestroy(ModuleConfig $module, EntityConfig $entity, string $dir): string
    {
        $path = "{$dir}/{$entity->modelName}DestroyTest.php";
        $namespace = "Modules\\{$module->name}\\Tests\\Feature";
        $modelImport = "Modules\\{$module->name}\\Models\\{$entity->modelName}";
        $type = $entity->jsonApiType;
        $model = $entity->modelName;
        $lcModel = lcfirst($model);

        $content = <<<PHP
<?php

namespace {$namespace};

use Tests\TestCase;
use {$modelImport};

class {$model}DestroyTest extends TestCase
{
    public function test_admin_can_delete_{$model}(): void
    {
        \$admin = \$this->getAdminUser();
        \${$lcModel} = {$model}::factory()->create();

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->delete("/api/v1/{$type}/{\${$lcModel}->id}");

        \$response->assertNoContent();
        \$this->assertDatabaseMissing('{$entity->tableName}', ['id' => \${$lcModel}->id]);
    }

    public function test_guest_cannot_delete_{$model}(): void
    {
        \${$lcModel} = {$model}::factory()->create();

        \$response = \$this->jsonApi()
            ->expects('{$type}')
            ->delete("/api/v1/{$type}/{\${$lcModel}->id}");

        \$response->assertStatus(401);
        \$this->assertDatabaseHas('{$entity->tableName}', ['id' => \${$lcModel}->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_{$model}(): void
    {
        \$admin = \$this->getAdminUser();

        \$response = \$this->actingAs(\$admin, 'sanctum')
            ->jsonApi()
            ->expects('{$type}')
            ->delete('/api/v1/{$type}/99999');

        \$response->assertStatus(404);
    }
}

PHP;

        File::put($path, $content);
        return $path;
    }

    // ===== Helpers =====

    private function buildStoreData(EntityConfig $entity, bool $minimalOnly): string
    {
        $lines = [];
        foreach ($entity->fields as $field) {
            if ($minimalOnly && !$field->isRequired && !$field->hasDefault) continue;
            $lines[] = "                '{$field->apiName}' => {$field->getTestValue()},";
        }
        return implode("\n", $lines);
    }

    private function buildDatabaseAssert(EntityConfig $entity): string
    {
        $pairs = [];
        foreach ($entity->fields as $field) {
            if (!$field->isRequired) continue;
            if ($field->type === 'json' || $field->type === 'datetime' || $field->type === 'date') continue;
            $pairs[] = "'{$field->dbName}' => {$field->getTestValue()}";
            if (count($pairs) >= 3) break;
        }
        return implode(', ', $pairs);
    }

    private function buildRelationshipImports(ModuleConfig $module, EntityConfig $entity): string
    {
        $lines = [];
        foreach ($entity->getBelongsToRelationships() as $rel) {
            if (isset($rel['module'])) {
                $lines[] = "use Modules\\{$rel['module']}\\Models\\{$rel['model']};";
            } else {
                $lines[] = "use Modules\\{$module->name}\\Models\\{$rel['model']};";
            }
        }
        return implode("\n", $lines);
    }

    private function buildRelationshipSetup(EntityConfig $entity): string
    {
        $lines = [];
        foreach ($entity->getBelongsToRelationships() as $rel) {
            $var = lcfirst($rel['model']);
            $lines[] = "        \${$var} = {$rel['model']}::factory()->create();";
        }
        if (!empty($lines)) {
            return "\n" . implode("\n", $lines) . "\n";
        }
        return '';
    }

    private function buildRelationshipData(EntityConfig $entity): string
    {
        $rels = $entity->getBelongsToRelationships();
        if (empty($rels)) return '';

        $lines = [];
        $lines[] = "            'relationships' => [";
        foreach ($rels as $rel) {
            $var = lcfirst($rel['model']);
            $type = NamingHelper::toJsonApiType($rel['model']);
            $lines[] = "                '{$var}' => [";
            $lines[] = "                    'data' => [";
            $lines[] = "                        'type' => '{$type}',";
            $lines[] = "                        'id' => (string) \${$var}->id,";
            $lines[] = "                    ],";
            $lines[] = "                ],";
        }
        $lines[] = "            ],";

        return implode("\n", $lines);
    }

    private function getFilterTestValue($field): string
    {
        if ($field->type === 'boolean') return 'true';
        if ($field->type === 'string' || $field->type === 'enum') return "'test string'";
        if ($field->type === 'integer') return '42';
        if ($field->type === 'decimal') return '99.99';
        return "'test'";
    }

    private function getFilterQueryValue($field): string
    {
        if ($field->type === 'boolean') return '1';
        return 'test';
    }
}
