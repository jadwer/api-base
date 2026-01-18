<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Product\Models\Brand;
use App\Models\User;

class BrandLeadTimeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

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
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/brands/{$brand->id}", [
                'data' => [
                    'type' => 'brands',
                    'id' => (string) $brand->id,
                    'attributes' => [
                        'defaultLeadTime' => '15 dias habiles',
                    ],
                ],
            ]);

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
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'default_lead_time' => '3-4 semanas',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/brands/{$brand->id}");

        $response->assertOk()
            ->assertJsonPath('data.attributes.defaultLeadTime', '3-4 semanas');
    }
}
