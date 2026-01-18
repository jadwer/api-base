<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteIndexTest extends TestCase
{
    public function test_admin_can_list_quotes(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        Quote::factory()->count(3)->create(['contact_id' => $contact->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'quoteNumber',
                        'status',
                        'quoteDate',
                        'validUntil',
                        'subtotalAmount',
                        'taxAmount',
                        'totalAmount',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_quotes_by_quote_number(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'quote_number' => 'QT-ZZZ',
        ]);
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'quote_number' => 'QT-AAA',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes?sort=quoteNumber');

        $response->assertOk();
    }

    public function test_admin_can_filter_quotes_by_status(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        Quote::factory()->count(2)->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);
        Quote::factory()->count(1)->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes?filter[status]=draft');

        $response->assertOk();

        // All returned quotes should have draft status
        $data = $response->json('data');
        foreach ($data as $quote) {
            $this->assertEquals('draft', $quote['attributes']['status']);
        }
    }

    public function test_tech_user_can_list_quotes(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes');

        $response->assertOk();
    }

    public function test_customer_can_list_quotes(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes');

        $response->assertOk();
    }

    public function test_guest_cannot_list_quotes(): void
    {
        $response = $this->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes');

        $response->assertStatus(401);
    }

    public function test_can_paginate_quotes(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        Quote::factory()->count(25)->create(['contact_id' => $contact->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }

    public function test_can_filter_quotes_by_contact(): void
    {
        $admin = $this->getAdminUser();

        $contact1 = Contact::factory()->customer()->create();
        $contact2 = Contact::factory()->customer()->create();

        Quote::factory()->count(2)->create(['contact_id' => $contact1->id]);
        Quote::factory()->count(3)->create(['contact_id' => $contact2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get("/api/v1/quotes?filter[contact]={$contact1->id}");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_sort_quotes_by_total_amount(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'total_amount' => 1000,
        ]);
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'total_amount' => 500,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes?sort=totalAmount');

        $response->assertOk();
    }
}
