<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteItemStoreTest extends TestCase
{
    public function test_admin_can_create_quote_item(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create(['price' => 100]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'attributes' => [
                    'quantity' => 5,
                    'unitPrice' => 100,
                    'quotedPrice' => 95,
                    'discountPercentage' => 5,
                    'taxRate' => 16,
                ],
                'relationships' => [
                    'quote' => [
                        'data' => [
                            'type' => 'quotes',
                            'id' => (string) $quote->id,
                        ]
                    ],
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => (string) $product->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quote-items');

        $response->assertCreated();
        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_guest_cannot_create_quote_item(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $response = $this->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'attributes' => [
                    'quantity' => 1,
                    'unitPrice' => 100,
                ],
                'relationships' => [
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => (string) $quote->id]
                    ],
                    'product' => [
                        'data' => ['type' => 'products', 'id' => (string) $product->id]
                    ]
                ]
            ])
            ->post('/api/v1/quote-items');

        $response->assertStatus(401);
    }

    public function test_quantity_is_required(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'attributes' => [
                    'unitPrice' => 100,
                ],
                'relationships' => [
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => (string) $quote->id]
                    ],
                    'product' => [
                        'data' => ['type' => 'products', 'id' => (string) $product->id]
                    ]
                ]
            ])
            ->post('/api/v1/quote-items');

        $response->assertStatus(422);
    }

    public function test_quote_is_required(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'attributes' => [
                    'quantity' => 1,
                    'unitPrice' => 100,
                ],
                'relationships' => [
                    'product' => [
                        'data' => ['type' => 'products', 'id' => (string) $product->id]
                    ]
                ]
            ])
            ->post('/api/v1/quote-items');

        $response->assertStatus(422);
    }

    public function test_product_is_required(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'attributes' => [
                    'quantity' => 1,
                    'unitPrice' => 100,
                ],
                'relationships' => [
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => (string) $quote->id]
                    ]
                ]
            ])
            ->post('/api/v1/quote-items');

        $response->assertStatus(422);
    }

    public function test_quoted_price_defaults_to_unit_price(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create(['price' => 150]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'attributes' => [
                    'quantity' => 2,
                    'unitPrice' => 150,
                    // quotedPrice not provided - should default
                ],
                'relationships' => [
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => (string) $quote->id]
                    ],
                    'product' => [
                        'data' => ['type' => 'products', 'id' => (string) $product->id]
                    ]
                ]
            ])
            ->post('/api/v1/quote-items');

        $response->assertCreated();
    }

    public function test_tax_rate_defaults_to_16_percent(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'attributes' => [
                    'quantity' => 1,
                    'unitPrice' => 100,
                    // taxRate not provided
                ],
                'relationships' => [
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => (string) $quote->id]
                    ],
                    'product' => [
                        'data' => ['type' => 'products', 'id' => (string) $product->id]
                    ]
                ]
            ])
            ->post('/api/v1/quote-items');

        $response->assertCreated();
    }
}
