<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceIndexTest extends TestCase
{



    public function test_admin_can_list_JournalSequences(): void
    {
        $admin = $this->getAdminUser();
        
        JournalSequence::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get('/api/v1/journal-sequences');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'journal_id',
                        'fiscalYear',
                        'currentNumber',
                        'metadata',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_JournalSequences_by_createdAt(): void
    {
        $admin = $this->getAdminUser();
        
        JournalSequence::factory()->create([]);
        JournalSequence::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get('/api/v1/journal-sequences?sort=createdAt');

        $response->assertOk();
    }

    public function test_admin_can_filter_JournalSequences_by_fiscal_year(): void
    {
        $admin = $this->getAdminUser();

        JournalSequence::factory()->create(['fiscal_year' => 2024]);
        JournalSequence::factory()->create(['fiscal_year' => 2025]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get('/api/v1/journal-sequences?filter[fiscal_year]=2025');

        $response->assertOk();
    }

    public function test_tech_user_can_list_JournalSequences_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get('/api/v1/journal-sequences');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_JournalSequences(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get('/api/v1/journal-sequences');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_JournalSequences(): void
    {
        $response = $this->jsonApi()
            ->expects('journal-sequences')
            ->get('/api/v1/journal-sequences');

        $response->assertStatus(401);
    }

    public function test_can_paginate_JournalSequences(): void
    {
        $admin = $this->getAdminUser();
        
        JournalSequence::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->get('/api/v1/journal-sequences?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
