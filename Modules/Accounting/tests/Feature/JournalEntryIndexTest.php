<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryIndexTest extends TestCase
{



    public function test_admin_can_list_JournalEntries(): void
    {
        $admin = $this->getAdminUser();
        
        JournalEntry::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get('/api/v1/journal-entries');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'journalId',
                        'fiscalPeriodId',
                        'number',
                        'date',
                        'reference',
                        'description',
                        'totalDebit',
                        'totalCredit',
                        'status',
                        'approvedAt',
                        'approvedById',
                        'postedAt',
                        'postedById',
                        'reversalOfId',
                        'reversalReason',
                        'metadata',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_JournalEntries_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        JournalEntry::factory()->create(['status' => 'active']);
        JournalEntry::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get('/api/v1/journal-entries?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_JournalEntries_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        JournalEntry::factory()->create(['status' => 'active']);
        JournalEntry::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get('/api/v1/journal-entries?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_JournalEntries_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get('/api/v1/journal-entries');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_JournalEntries(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get('/api/v1/journal-entries');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_JournalEntries(): void
    {
        $response = $this->jsonApi()
            ->expects('journal-entries')
            ->get('/api/v1/journal-entries');

        $response->assertStatus(401);
    }

    public function test_can_paginate_JournalEntries(): void
    {
        $admin = $this->getAdminUser();
        
        JournalEntry::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->get('/api/v1/journal-entries?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
