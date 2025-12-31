<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Modules\Inventory\Services\LotTraceabilityService;
use Laravel\Sanctum\Sanctum;

/**
 * LotTraceabilityTest
 *
 * IV-M003: Tests for lot traceability features
 */
class LotTraceabilityTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    /**
     * Test batch genealogy endpoint returns movement history.
     */
    public function test_genealogy_returns_batch_movement_history(): void
    {
        $user = $this->getAdminUser();
        $batch = ProductBatch::first() ?? ProductBatch::factory()->create();

        // Create movement for this batch
        InventoryMovement::factory()->create([
            'product_id' => $batch->product_id,
            'warehouse_id' => $batch->warehouse_id,
            'product_batch_id' => $batch->id,
            'movement_type' => 'entry',
            'quantity' => 100,
            'status' => 'completed',
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/v1/lot-traceability/{$batch->id}/genealogy");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'batch_id',
                    'batch_number',
                    'movement_count',
                    'timeline',
                ],
            ]);
    }

    /**
     * Test trace backward endpoint.
     */
    public function test_trace_backward_returns_origin_info(): void
    {
        $user = $this->getAdminUser();
        $batch = ProductBatch::first() ?? ProductBatch::factory()->create();

        $response = $this->getJson("/api/v1/lot-traceability/{$batch->id}/trace-backward");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'batch_id',
                    'batch_number',
                    'origin',
                ],
            ]);
    }

    /**
     * Test trace forward endpoint.
     */
    public function test_trace_forward_returns_consumption_info(): void
    {
        $user = $this->getAdminUser();
        $batch = ProductBatch::first() ?? ProductBatch::factory()->create();

        $response = $this->getJson("/api/v1/lot-traceability/{$batch->id}/trace-forward");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'batch_id',
                    'batch_number',
                    'consumption',
                ],
            ]);
    }

    /**
     * Test FEFO batch selection.
     */
    public function test_select_fefo_returns_batches_by_expiration(): void
    {
        $user = $this->getAdminUser();
        $product = Product::first() ?? Product::factory()->create();
        $warehouse = Warehouse::first() ?? Warehouse::factory()->create();

        $response = $this->postJson('/api/v1/lot-traceability/select-fefo', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'warehouse_id',
                    'required_quantity',
                    'fulfilled',
                    'selected_batches',
                ],
            ]);
    }

    /**
     * Test expiring soon endpoint.
     */
    public function test_expiring_soon_returns_batches_near_expiration(): void
    {
        $user = $this->getAdminUser();

        $response = $this->getJson('/api/v1/lot-traceability/expiring-soon?days=30');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['count', 'threshold_days'],
            ]);
    }

    /**
     * Test expired endpoint.
     */
    public function test_expired_returns_past_expiration_batches(): void
    {
        $user = $this->getAdminUser();

        $response = $this->getJson('/api/v1/lot-traceability/expired');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['count', 'total_value_at_risk'],
            ]);
    }

    /**
     * Test batch search endpoint.
     */
    public function test_search_finds_batches_by_lot_number(): void
    {
        $user = $this->getAdminUser();

        $response = $this->getJson('/api/v1/lot-traceability/search?q=BATCH');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['count', 'search_term'],
            ]);
    }

    /**
     * Test recall impact analysis endpoint.
     */
    public function test_recall_impact_returns_analysis(): void
    {
        $user = $this->getAdminUser();
        $batch = ProductBatch::first() ?? ProductBatch::factory()->create();

        $response = $this->getJson("/api/v1/lot-traceability/{$batch->id}/recall-impact");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'batch_info',
                    'impact',
                    'recommended_actions',
                ],
            ]);
    }

    /**
     * Test unauthenticated access is denied.
     */
    public function test_unauthenticated_access_denied(): void
    {
        $batch = ProductBatch::first() ?? ProductBatch::factory()->create();

        $response = $this->getJson("/api/v1/lot-traceability/{$batch->id}/genealogy");

        $response->assertUnauthorized();
    }

    /**
     * Test LotTraceabilityService FEFO selection.
     */
    public function test_service_fefo_selection_algorithm(): void
    {
        $product = Product::first() ?? Product::factory()->create();
        $warehouse = Warehouse::first() ?? Warehouse::factory()->create();

        // Create batch with expiration
        ProductBatch::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'FEFO-TEST-' . uniqid(),
            'current_quantity' => 50,
            'reserved_quantity' => 0,
            'expiration_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $service = app(LotTraceabilityService::class);
        $result = $service->selectBatchesFEFO($product->id, $warehouse->id, 30);

        $this->assertArrayHasKey('fulfilled', $result);
        $this->assertArrayHasKey('selected_batches', $result);
    }

    /**
     * Test ProductBatch model FEFO scope.
     */
    public function test_product_batch_fefo_scope(): void
    {
        $batches = ProductBatch::fefo()->limit(5)->get();

        // Just verify the scope doesn't throw an error
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $batches);
    }

    /**
     * Test InventoryMovement model byBatch scope.
     */
    public function test_inventory_movement_by_batch_scope(): void
    {
        $batch = ProductBatch::first();

        if ($batch) {
            $movements = InventoryMovement::byBatch($batch->id)->get();
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $movements);
        } else {
            $this->markTestSkipped('No batches available for testing');
        }
    }
}
