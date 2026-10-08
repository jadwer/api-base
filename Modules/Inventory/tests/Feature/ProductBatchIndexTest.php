<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\Inventory\Models\ProductBatch;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductBatchIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'product-batches.index', 'guard_name' => 'api']);

        // Crear roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        Role::findByName('admin', 'api')->givePermissionTo('product-batches.index');
        $user->assignRole('admin');

        return $user;
    }

    private function idsFrom($response): array
    {
        return collect($response->json('data'))->pluck('id')->map(fn ($id) => (string) $id)->sort()->values()->all();
    }

    public function test_admin_can_search_product_batches_by_batch_number()
    {
        $admin = $this->createAdmin();

        $match = ProductBatch::factory()->create(['batch_number' => 'ZQX-LOTE-0001']);
        ProductBatch::factory()->create(['batch_number' => 'OTRO-0002']);

        // Busqueda parcial sobre el numero de lote
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches?filter[search]=ZQX-LOTE');

        $response->assertStatus(200);
        $this->assertSame([(string) $match->id], $this->idsFrom($response));
    }

    public function test_admin_can_search_product_batches_by_product_sku()
    {
        $admin = $this->createAdmin();

        $product = Product::factory()->create(['sku' => 'SKUZQX-777']);
        $match = ProductBatch::factory()->create(['product_id' => $product->id, 'batch_number' => 'B-0001']);
        ProductBatch::factory()->create(['batch_number' => 'B-0002']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches?filter[search]=SKUZQX');

        $response->assertStatus(200);
        $this->assertSame([(string) $match->id], $this->idsFrom($response));
    }

    public function test_admin_can_filter_product_batches_by_status()
    {
        $admin = $this->createAdmin();

        $active = ProductBatch::factory()->create(['status' => 'active']);
        $quarantine = ProductBatch::factory()->quarantine()->create();
        $expired = ProductBatch::factory()->expired()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches?filter[status]=quarantine');

        $response->assertStatus(200);
        // El seeder de pruebas puede traer lotes; todos deben estar en cuarentena
        $ids = $this->idsFrom($response);
        $this->assertContains((string) $quarantine->id, $ids);
        $this->assertNotContains((string) $active->id, $ids);
        $this->assertNotContains((string) $expired->id, $ids);
        foreach ($response->json('data') as $item) {
            $this->assertEquals('quarantine', $item['attributes']['status']);
        }
    }

    public function test_search_combined_with_status_does_not_leak()
    {
        $admin = $this->createAdmin();

        // Ambos lotes coinciden con el termino; solo uno esta activo
        $active = ProductBatch::factory()->create([
            'batch_number' => 'ZQXFUGA-1',
            'status' => 'active',
        ]);
        ProductBatch::factory()->quarantine()->create([
            'batch_number' => 'ZQXFUGA-2',
        ]);
        // El termino tambien coincide por proveedor en un lote en cuarentena
        ProductBatch::factory()->quarantine()->create([
            'batch_number' => 'SIN-COINCIDENCIA',
            'supplier_name' => 'Proveedor ZQXFUGA',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get('/api/v1/product-batches?filter[search]=ZQXFUGA&filter[status]=active');

        $response->assertStatus(200);
        $this->assertSame([(string) $active->id], $this->idsFrom($response));
    }

    public function test_dates_are_serialized_without_time(): void
    {
        $batch = ProductBatch::factory()->create(['expiration_date' => '2026-10-25']);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->get("/api/v1/product-batches/{$batch->id}");

        $response->assertOk();
        $this->assertSame('2026-10-25', $response->json('data.attributes.expirationDate'));
    }
}
