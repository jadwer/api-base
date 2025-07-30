<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

// Simular el método de generación de test
function generateTestFile($moduleName, $entityName, $entity) {
    
    // Obtener el stub template
    $stubContent = '<?php

namespace Modules\{{moduleName}}\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\{{moduleName}}\Models\{{modelName}};

class {{modelName}}IndexTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where(\'email\', \'admin@example.com\')->firstOrFail();
    }

    public function test_can_list_{{resourceType}}()
    {
        // Arrange
        $user = $this->getAdminUser();
        $this->actingAs($user);

        // Create some test data
        {{modelName}}::factory()->count(3)->create();

        // Act
        $response = $this->get(\'/api/v1/{{resourceType}}\');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            \'data\' => [
                \'*\' => [
                    \'type\',
                    \'id\',
                    \'attributes\' => [
                        {{testableFields}}
                    ]
                ]
            ],
            \'meta\' => [
                \'pagination\' => [
                    \'count\',
                    \'currentPage\',
                    \'perPage\',
                    \'total\',
                    \'totalPages\'
                ]
            ]
        ]);
    }

    public function test_can_list_{{resourceType}}_with_sorting()
    {
        // Arrange
        $user = $this->getAdminUser();
        $this->actingAs($user);

        {{modelName}}::factory()->count(5)->create();

        // Act - Test sorting by {{sortableField}}
        $response = $this->get(\'/api/v1/{{resourceType}}?sort={{sortableField}}\');

        // Assert
        $response->assertOk();
        $data = $response->json(\'data\');
        $this->assertCount(5, $data);
    }

    public function test_can_list_{{resourceType}}_with_filtering()
    {
        // Arrange
        $user = $this->getAdminUser();
        $this->actingAs($user);

        // Create test instances with specific data for filtering
        $specific{{modelName}} = {{modelName}}::factory()->create([
            \'{{filterableField}}\' => \'test-filter-value\'
        ]);
        
        {{modelName}}::factory()->count(2)->create([
            \'{{filterableField}}\' => \'other-value\'
        ]);

        // Act - Filter by specific value
        $response = $this->get(\'/api/v1/{{resourceType}}?filter[{{filterableField}}]=test-filter-value\');

        // Assert
        $response->assertOk();
        $data = $response->json(\'data\');
        $this->assertCount(1, $data);
        $this->assertEquals($specific{{modelName}}->id, $data[0][\'id\']);
    }

    public function test_can_list_{{resourceType}}_with_pagination()
    {
        // Arrange
        $user = $this->getAdminUser();
        $this->actingAs($user);

        {{modelName}}::factory()->count(25)->create();

        // Act
        $response = $this->get(\'/api/v1/{{resourceType}}?page[size]=10&page[number]=1\');

        // Assert
        $response->assertOk();
        $response->assertJsonPath(\'meta.pagination.perPage\', 10);
        $response->assertJsonPath(\'meta.pagination.currentPage\', 1);
        $response->assertJsonCount(10, \'data\');
    }
}';

    // Variables de reemplazo
    $testableFields = "'name',\n                        'isActive'"; // Campos básicos por ahora
    $factoryFields = ""; // Vacío por ahora
    
    // Hacer el reemplazo
    $testContent = str_replace([
        '{{moduleName}}',
        '{{modelName}}',
        '{{modelPlural}}',
        '{{resourceType}}',
        '{{testableFields}}',
        '{{factoryFields}}',
        '{{sortableField}}',
        '{{filterableField}}',
        '{{tableName}}'
    ], [
        $moduleName,
        $entityName,
        Str::plural($entityName),
        Str::kebab(Str::plural($entityName)),
        $testableFields,
        $factoryFields,
        'name', // sortable field
        'name', // filterable field
        Str::snake(Str::plural($entityName))
    ], $stubContent);
    
    echo "=== CONTENIDO GENERADO ===\n";
    echo $testContent;
    echo "\n=== FIN CONTENIDO ===\n";
    
    return $testContent;
}

// Datos de prueba
$moduleName = 'Ecommerce';
$entityName = 'Coupon';
$entity = [
    'name' => 'Coupon',
    'fields' => [
        ['name' => 'code', 'type' => 'string'],
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'description', 'type' => 'text']
    ]
];

// Generar el test
generateTestFile($moduleName, $entityName, $entity);
