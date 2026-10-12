<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;

class QuoteItemIndexTest extends TestCase
{
    public function test_admin_can_list_quote_items(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        QuoteItem::factory()->count(3)->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->get('/api/v1/quote-items');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'quantity',
                        'unitPrice',
                        'quotedPrice',
                        'discountPercentage',
                        'taxRate',
                        'subtotalBeforeDiscount',
                        'total',
                    ]
                ]
            ]
        ]);
    }

    /**
     * El portal del cliente muestra la unidad real del producto (antes "pz"
     * fijo): la partida acepta include=product.unit.
     */
    public function test_can_include_product_unit(): void
    {
        $admin = $this->getAdminUser();

        $unit = Unit::factory()->create(['name' => 'Litro', 'code' => 'LTR']);
        $quote = Quote::factory()->create(['contact_id' => Contact::factory()->customer()->create()->id]);
        $product = Product::factory()->create(['unit_id' => $unit->id]);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'product_id' => $product->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->get("/api/v1/quote-items?filter[quote]={$quote->id}&include=product,product.unit");

        $response->assertOk();
        $units = collect($response->json('included'))->where('type', 'units')->values();
        $this->assertCount(1, $units);
        $this->assertSame('Litro', $units[0]['attributes']['name']);
        $this->assertSame((string) $unit->id, (string) $units[0]['id']);
    }

    public function test_tech_can_list_quote_items(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->get('/api/v1/quote-items');

        $response->assertOk();
    }

    public function test_customer_can_list_quote_items(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->get('/api/v1/quote-items');

        $response->assertOk();
    }

    public function test_guest_cannot_list_quote_items(): void
    {
        $response = $this->jsonApi()
            ->expects('quote-items')
            ->get('/api/v1/quote-items');

        $response->assertStatus(401);
    }

    public function test_can_filter_by_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote1 = Quote::factory()->create(['contact_id' => $contact->id]);
        $quote2 = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        QuoteItem::factory()->count(2)->create([
            'quote_id' => $quote1->id,
            'product_id' => $product->id,
        ]);
        QuoteItem::factory()->count(3)->create([
            'quote_id' => $quote2->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->get("/api/v1/quote-items?filter[quote]={$quote1->id}");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_paginate_quote_items(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        QuoteItem::factory()->count(25)->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->get('/api/v1/quote-items?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
    }
}
