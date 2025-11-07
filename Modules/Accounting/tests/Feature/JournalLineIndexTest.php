<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalLine;

class JournalLineIndexTest extends TestCase
{



    public function test_admin_can_list_JournalLines(): void
    {
        $admin = $this->getAdminUser();
        
        JournalLine::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get('/api/v1/journal-lines');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'journalEntryId',
                        'accountId',
                        'contactId',
                        'debit',
                        'credit',
                        'description',
                        'reference',
                        'metadata',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_JournalLines_by_reference(): void
    {
        $admin = $this->getAdminUser();
        
        JournalLine::factory()->create(['reference' => 'test string']);
        JournalLine::factory()->create(['reference' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get('/api/v1/journal-lines?sort=reference');

        $response->assertOk();
    }

    public function test_admin_can_filter_JournalLines_by_reference(): void
    {
        $admin = $this->getAdminUser();

        JournalLine::factory()->create(['reference' => 'REF-001']);
        JournalLine::factory()->create(['reference' => 'REF-002']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get('/api/v1/journal-lines?filter[reference]=REF-001');

        $response->assertOk();
    }

    public function test_tech_user_can_list_JournalLines_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get('/api/v1/journal-lines');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_JournalLines(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get('/api/v1/journal-lines');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_JournalLines(): void
    {
        $response = $this->jsonApi()
            ->expects('journal-lines')
            ->get('/api/v1/journal-lines');

        $response->assertStatus(401);
    }

    public function test_can_paginate_JournalLines(): void
    {
        $admin = $this->getAdminUser();
        
        JournalLine::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->get('/api/v1/journal-lines?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
