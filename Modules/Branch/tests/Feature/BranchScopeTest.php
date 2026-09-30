<?php

namespace Modules\Branch\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\SalesOrder;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Restriccion real por sucursal (decision de Gabino 2026-09-24): un usuario
 * que no es god/admin/customer solo ve documentos de sus sucursales.
 */
class BranchScopeTest extends TestCase
{
    private Branch $toluca;

    protected function setUp(): void
    {
        parent::setUp();
        $this->toluca = Branch::factory()->create(['code' => 'TOL', 'name' => 'Toluca']);
    }

    private function tolucaSeller(): User
    {
        $user = User::factory()->create(['branch_id' => $this->toluca->id]);
        $user->givePermissionTo(['quotes.index', 'quotes.show', 'quotes.store', 'sales-orders.index', 'sales-orders.show']);

        return $user->fresh();
    }

    public function test_restricted_user_only_lists_documents_of_his_branches(): void
    {
        $inToluca = Quote::factory()->create(['branch_id' => $this->toluca->id]);
        Quote::factory()->create(['branch_id' => Branch::mainId()]);

        $response = $this->actingAs($this->tolucaSeller(), 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes');

        $response->assertFetchedMany([$inToluca]);
    }

    public function test_restricted_user_gets_404_on_another_branch_document(): void
    {
        $inMatriz = SalesOrder::factory()->create(['branch_id' => Branch::mainId()]);

        $this->actingAs($this->tolucaSeller(), 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get("/api/v1/sales-orders/{$inMatriz->id}")
            ->assertNotFound();
    }

    public function test_access_branches_extend_what_the_user_sees(): void
    {
        $user = $this->tolucaSeller();
        $user->branches()->sync([Branch::mainId()]);
        $a = Quote::factory()->create(['branch_id' => $this->toluca->id]);
        $b = Quote::factory()->create(['branch_id' => Branch::mainId()]);

        $response = $this->actingAs($user->fresh(), 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains((string) $a->id, $ids);
        $this->assertContains((string) $b->id, $ids);
    }

    public function test_admin_sees_every_branch(): void
    {
        Quote::factory()->create(['branch_id' => $this->toluca->id]);
        Quote::factory()->create(['branch_id' => Branch::mainId()]);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes');

        $this->assertSame(Quote::withoutGlobalScopes()->count(), count($response->json('data')));
    }

    public function test_restricted_user_cannot_create_in_a_branch_without_access(): void
    {
        $contact = Quote::factory()->create()->contact;

        $response = $this->actingAs($this->tolucaSeller(), 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'contactId' => $contact->id,
                    'quoteDate' => now()->toDateString(),
                    'branchId' => Branch::mainId(),
                ],
            ])
            ->post('/api/v1/quotes');

        $response->assertStatus(422);
    }

    public function test_restricted_user_creates_in_his_branch_by_default(): void
    {
        $contact = Quote::factory()->create()->contact;

        $response = $this->actingAs($this->tolucaSeller(), 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => ['contactId' => $contact->id, 'quoteDate' => now()->toDateString()],
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
        $this->assertSame($this->toluca->id, Quote::withoutGlobalScopes()->find($response->id())->branch_id);
    }

    public function test_without_authenticated_user_nothing_is_restricted(): void
    {
        Quote::factory()->create(['branch_id' => $this->toluca->id]);
        Quote::factory()->create(['branch_id' => Branch::mainId()]);

        $this->assertSame(Quote::withoutGlobalScopes()->count(), Quote::count());
    }

    public function test_stock_is_shared_but_filterable_by_branch(): void
    {
        $warehouse = Warehouse::factory()->create(['branch_id' => $this->toluca->id]);
        $stock = Stock::factory()->create(['warehouse_id' => $warehouse->id]);
        Stock::factory()->create();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->filter(['branch' => $this->toluca->id])
            ->get('/api/v1/stocks');

        $response->assertFetchedMany([$stock]);
    }

    public function test_profile_exposes_branch_and_accessible_branches(): void
    {
        $user = $this->tolucaSeller();
        $user->branches()->sync([Branch::mainId()]);

        $attributes = $this->actingAs($user->fresh(), 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->json('data.attributes');

        $this->assertSame((string) $this->toluca->id, $attributes['branch_id']);
        $this->assertEqualsCanonicalizing(
            [(string) $this->toluca->id, (string) Branch::mainId()],
            $attributes['branch_ids']
        );
    }

    public function test_warehouse_exposes_and_accepts_branch_id(): void
    {
        $admin = $this->getAdminUser();
        $admin->givePermissionTo(['warehouses.store', 'warehouses.index']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('warehouses')
            ->withData([
                'type' => 'warehouses',
                'attributes' => [
                    'name' => 'Bodega Toluca',
                    'slug' => 'bodega-toluca',
                    'code' => 'WH-TOL',
                    'warehouseType' => 'main',
                    'isActive' => true,
                    'branchId' => $this->toluca->id,
                ],
            ])
            ->post('/api/v1/warehouses');

        $response->assertCreated();
        $this->assertSame($this->toluca->id, (int) $response->json('data.attributes.branchId'));
        $this->assertSame($this->toluca->id, (int) Warehouse::where('code', 'WH-TOL')->value('branch_id'));
    }

    public function test_cfdi_invoices_filter_by_branch(): void
    {
        $inToluca = \Modules\Billing\Models\CFDIInvoice::factory()->create(['branch_id' => $this->toluca->id]);
        \Modules\Billing\Models\CFDIInvoice::factory()->create(['branch_id' => Branch::mainId()]);

        $ids = collect($this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('cfdi-invoices')
            ->filter(['branch' => (string) $this->toluca->id])
            ->get('/api/v1/cfdi-invoices')
            ->assertOk()
            ->json('data'))->pluck('id')->all();

        $this->assertSame([(string) $inToluca->id], $ids);
    }

    public function test_quote_from_cart_accepts_branch(): void
    {
        $admin = $this->getAdminUser();
        $contact = \Modules\Contacts\Models\Contact::factory()->customer()->create();
        $cart = \Modules\Ecommerce\Models\ShoppingCart::factory()->create(['user_id' => $admin->id]);
        \Modules\Ecommerce\Models\CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => \Modules\Product\Models\Product::factory()->create(['price' => 100])->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $id = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart->id,
                'contact_id' => $contact->id,
                'branch_id' => $this->toluca->id,
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->assertSame($this->toluca->id, (int) Quote::withoutGlobalScopes()->find($id)->branch_id);
    }

    public function test_customer_sees_his_quotes_of_any_branch(): void
    {
        $customer = User::factory()->create(['branch_id' => Branch::mainId()]);
        $customer->assignRole('customer');
        $customer->givePermissionTo('quotes.index');
        $contact = \Modules\Contacts\Models\Contact::factory()->customer()->create(['email' => $customer->email]);
        $inToluca = Quote::factory()->create(['branch_id' => $this->toluca->id, 'contact_id' => $contact->id]);
        Quote::factory()->create(['branch_id' => $this->toluca->id]);

        $response = $this->actingAs($customer->fresh(), 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes');

        $response->assertFetchedMany([$inToluca]);
    }

    public function test_quote_pdf_prints_the_real_branch(): void
    {
        $quote = Quote::factory()->create(['branch_id' => $this->toluca->id, 'metadata' => ['branch' => 'Matriz']]);

        $generator = app(\Modules\Sales\Services\QuotePDFGenerator::class);
        $prepare = new \ReflectionMethod($generator, 'prepareData');
        $data = $prepare->invoke($generator, $quote->fresh());

        $this->assertSame('Toluca', $data['branch']);
    }
}
