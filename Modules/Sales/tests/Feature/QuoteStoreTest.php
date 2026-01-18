<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteStoreTest extends TestCase
{
    public function test_admin_can_create_quote(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-TEST-001',
                    'status' => 'draft',
                    'quoteDate' => now()->toDateString(),
                    'validUntil' => now()->addDays(30)->toDateString(),
                    'subtotalAmount' => 1000,
                    'taxAmount' => 160,
                    'totalAmount' => 1160,
                    'currency' => 'MXN',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
        $this->assertDatabaseHas('quotes', [
            'quote_number' => 'QT-TEST-001',
            'contact_id' => $contact->id,
        ]);
    }

    public function test_tech_can_create_quote(): void
    {
        $tech = $this->getTechUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-TECH-001',
                    'status' => 'draft',
                    'quoteDate' => now()->toDateString(),
                    'validUntil' => now()->addDays(30)->toDateString(),
                    'subtotalAmount' => 500,
                    'taxAmount' => 80,
                    'totalAmount' => 580,
                    'currency' => 'MXN',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
    }

    public function test_customer_can_create_quote(): void
    {
        $customer = $this->getCustomerUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-CUST-001',
                    'status' => 'draft',
                    'quoteDate' => now()->toDateString(),
                    'validUntil' => now()->addDays(30)->toDateString(),
                    'subtotalAmount' => 200,
                    'taxAmount' => 32,
                    'totalAmount' => 232,
                    'currency' => 'MXN',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
    }

    public function test_guest_cannot_create_quote(): void
    {
        $contact = Contact::factory()->customer()->create();

        $response = $this->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-GUEST-001',
                    'status' => 'draft',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertStatus(401);
    }

    public function test_quote_number_is_required(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'status' => 'draft',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertStatus(422);
    }

    public function test_contact_is_required(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-NO-CONTACT',
                    'status' => 'draft',
                ],
            ])
            ->post('/api/v1/quotes');

        $response->assertStatus(422);
    }

    public function test_status_defaults_to_draft(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-DEFAULT-STATUS',
                    'quoteDate' => now()->toDateString(),
                    'validUntil' => now()->addDays(30)->toDateString(),
                    'subtotalAmount' => 100,
                    'taxAmount' => 16,
                    'totalAmount' => 116,
                    'currency' => 'MXN',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
    }

    public function test_quote_number_must_be_unique(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'quote_number' => 'QT-DUPLICATE',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-DUPLICATE',
                    'status' => 'draft',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertStatus(422);
    }

    public function test_can_create_with_estimated_eta(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-WITH-ETA',
                    'status' => 'draft',
                    'quoteDate' => now()->toDateString(),
                    'validUntil' => now()->addDays(30)->toDateString(),
                    'estimatedEta' => '2-3 weeks',
                    'subtotalAmount' => 100,
                    'taxAmount' => 16,
                    'totalAmount' => 116,
                    'currency' => 'MXN',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
        $this->assertEquals('2-3 weeks', $response->json('data.attributes.estimatedEta'));
    }

    public function test_can_create_with_notes(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteNumber' => 'QT-WITH-NOTES',
                    'status' => 'draft',
                    'quoteDate' => now()->toDateString(),
                    'validUntil' => now()->addDays(30)->toDateString(),
                    'notes' => 'Special instructions for this quote',
                    'subtotalAmount' => 100,
                    'taxAmount' => 16,
                    'totalAmount' => 116,
                    'currency' => 'MXN',
                ],
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'type' => 'contacts',
                            'id' => (string) $contact->id,
                        ]
                    ]
                ]
            ])
            ->post('/api/v1/quotes');

        $response->assertCreated();
        $this->assertEquals('Special instructions for this quote', $response->json('data.attributes.notes'));
    }
}
