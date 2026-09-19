<?php

namespace Modules\Branch\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * Los documentos del ciclo nacen con la sucursal del usuario autenticado y,
 * sin usuario (checkout anonimo, seeders, jobs), con la Matriz.
 */
class DocumentBranchDefaultTest extends TestCase
{
    public function test_documents_created_without_user_land_in_main_branch(): void
    {
        $main = Branch::mainId();

        $this->assertSame($main, Quote::factory()->create()->fresh()->branch_id);
        $this->assertSame($main, SalesOrder::factory()->create()->fresh()->branch_id);
        $this->assertSame($main, Remission::factory()->create()->fresh()->branch_id);
        $this->assertSame($main, PurchaseOrder::factory()->create()->fresh()->branch_id);
    }

    public function test_quote_created_through_api_takes_the_users_branch(): void
    {
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $admin = $this->getAdminUser();
        $admin->update(['branch_id' => $toluca->id]);
        $contact = Quote::factory()->create()->contact;

        $response = $this->actingAs($admin->fresh(), 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'contactId' => $contact->id,
                    'quoteDate' => now()->toDateString(),
                ],
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
        $this->assertDatabaseHas('quotes', ['id' => $response->id(), 'branch_id' => $toluca->id]);
    }

    public function test_explicit_branch_wins_over_the_users_branch(): void
    {
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $queretaro = Branch::factory()->create(['code' => 'QRO']);
        $admin = $this->getAdminUser();
        $admin->update(['branch_id' => $toluca->id]);
        $contact = Quote::factory()->create()->contact;

        $response = $this->actingAs($admin->fresh(), 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'contactId' => $contact->id,
                    'quoteDate' => now()->toDateString(),
                    'branchId' => $queretaro->id,
                ],
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
        $this->assertDatabaseHas('quotes', ['id' => $response->id(), 'branch_id' => $queretaro->id]);
    }

    public function test_quotes_can_be_filtered_and_included_by_branch(): void
    {
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $admin = $this->getAdminUser();
        $inToluca = Quote::factory()->create(['branch_id' => $toluca->id]);
        Quote::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->filter(['branch' => $toluca->id])
            ->includePaths('branch')
            ->get('/api/v1/quotes');

        $response->assertFetchedMany([$inToluca]);
        $response->assertIsIncluded('branches', $toluca);
    }

    public function test_sales_orders_can_be_filtered_by_branch(): void
    {
        $toluca = Branch::factory()->create(['code' => 'TOL']);
        $admin = $this->getAdminUser();
        $inToluca = SalesOrder::factory()->create(['branch_id' => $toluca->id]);
        SalesOrder::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->filter(['branch' => $toluca->id])
            ->get('/api/v1/sales-orders');

        $response->assertFetchedMany([$inToluca]);
    }
}
