<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Product\Models\Brand;

class BrandLeadTimeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function brand_can_have_default_lead_time(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'default_lead_time' => '2-3 semanas',
        ]);

        $this->assertEquals('2-3 semanas', $brand->default_lead_time);
    }

    /** @test */
    public function brand_lead_time_is_nullable(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'default_lead_time' => null,
        ]);

        $this->assertNull($brand->default_lead_time);
    }

    /** @test */
    public function admin_can_update_brand_lead_time_via_api(): void
    {
        $admin = $this->getAdminUser();

        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('brands')
            ->withData([
                'type' => 'brands',
                'id' => (string) $brand->id,
                'attributes' => [
                    'defaultLeadTime' => '15 dias habiles',
                ],
            ])
            ->patch("/api/v1/brands/{$brand->id}");

        $response->assertOk();

        $brand->refresh();
        $this->assertEquals('15 dias habiles', $brand->default_lead_time);
    }

    /** @test */
    public function brand_lead_time_is_logged_in_activity(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'default_lead_time' => '1 semana',
        ]);

        $brand->update(['default_lead_time' => '2 semanas']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Brand::class,
            'subject_id' => $brand->id,
        ]);
    }

    /** @test */
    public function brand_lead_time_appears_in_api_response(): void
    {
        $admin = $this->getAdminUser();

        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'default_lead_time' => '3-4 semanas',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('brands')
            ->get("/api/v1/brands/{$brand->id}");

        $response->assertOk()
            ->assertJsonPath('data.attributes.defaultLeadTime', '3-4 semanas');
    }
}
