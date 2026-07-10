<?php

namespace Modules\SatCatalogs\Tests\Feature;

use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * WS9: SAT fields added to products (sat_clave_prod_serv, sat_clave_unidad,
 * product_type, tax_rate) and the effective_tax_rate accessor.
 */
class ProductSatFieldsTest extends TestCase
{
    public function test_effective_tax_rate_uses_tax_rate_when_set(): void
    {
        $product = Product::factory()->create(['iva' => false, 'tax_rate' => 8]);

        $this->assertSame(8.0, $product->effective_tax_rate);
    }

    public function test_effective_tax_rate_zero_is_not_confused_with_null(): void
    {
        $product = Product::factory()->create(['iva' => true, 'tax_rate' => 0]);

        $this->assertSame(0.0, $product->effective_tax_rate);
    }

    public function test_effective_tax_rate_falls_back_to_legacy_iva_flag(): void
    {
        $withIva = Product::factory()->create(['iva' => true, 'tax_rate' => null]);
        $withoutIva = Product::factory()->create(['iva' => false, 'tax_rate' => null]);

        $this->assertSame(16.0, $withIva->effective_tax_rate);
        $this->assertSame(0.0, $withoutIva->effective_tax_rate);
    }

    public function test_admin_can_update_sat_fields_via_json_api(): void
    {
        $admin = $this->getAdminUser();
        $this->actingAs($admin, 'sanctum');

        $product = Product::factory()->create();

        $data = [
            'type' => 'products',
            'id' => (string) $product->id,
            'attributes' => [
                'satClaveProdServ' => '12352301',
                'satClaveUnidad' => 'LTR',
                'productType' => 'raw_material',
                'taxRate' => 16,
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/products/{$product->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'attributes' => [
                    'satClaveProdServ' => '12352301',
                    'satClaveUnidad' => 'LTR',
                    'productType' => 'raw_material',
                    'taxRate' => 16,
                ],
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sat_clave_prod_serv' => '12352301',
            'sat_clave_unidad' => 'LTR',
            'product_type' => 'raw_material',
            'tax_rate' => 16,
        ]);
    }

    public function test_product_type_must_be_a_valid_value(): void
    {
        $admin = $this->getAdminUser();
        $this->actingAs($admin, 'sanctum');

        $product = Product::factory()->create();

        $response = $this->jsonApi()
            ->withData([
                'type' => 'products',
                'id' => (string) $product->id,
                'attributes' => ['productType' => 'invalid-type'],
            ])
            ->patch("/api/v1/products/{$product->id}");

        $response->assertStatus(422);
    }

    public function test_tax_rate_must_be_between_0_and_100(): void
    {
        $admin = $this->getAdminUser();
        $this->actingAs($admin, 'sanctum');

        $product = Product::factory()->create();

        $response = $this->jsonApi()
            ->withData([
                'type' => 'products',
                'id' => (string) $product->id,
                'attributes' => ['taxRate' => 150],
            ])
            ->patch("/api/v1/products/{$product->id}");

        $response->assertStatus(422);
    }
}
