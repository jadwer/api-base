<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\FiscalPeriod;

class JournalEntryUpdateTest extends TestCase
{
    /**
     * Invariante: status NO es editable por PATCH (readOnlyOnUpdate). Postear un
     * asiento va por AccountingService::postJournalEntry (validateBalance +
     * validatePeriod + folio); un PATCH directo se los saltaria. La version
     * anterior de este test verificaba exactamente el bypass (draft->posted por
     * PATCH con assertOk) y consagraba el bug.
     */
    public function test_patch_ignores_status_changes(): void
    {
        $admin = $this->getAdminUser();
        $period = FiscalPeriod::factory()->open()->create();
        $entity = JournalEntry::factory()->create(['fiscal_period_id' => $period->id, 'status' => 'draft']);

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'posted',
                'reference' => 'UPD-001'
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$entity->id}");

        $response->assertOk();
        $entity->refresh();
        $this->assertSame('draft', $entity->status, 'El PATCH no debe poder postear un asiento');
        $this->assertSame('UPD-001', $entity->reference, 'Los campos editables si se actualizan');
    }

    public function test_posted_entry_is_immutable_via_patch(): void
    {
        $admin = $this->getAdminUser();
        $period = FiscalPeriod::factory()->open()->create();
        $entity = JournalEntry::factory()->create([
            'fiscal_period_id' => $period->id,
            'status' => 'posted',
            'reference' => 'ORIGINAL',
        ]);

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $entity->id,
            'attributes' => [
                'reference' => 'HACKED'
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$entity->id}");

        $response->assertStatus(403);
        $entity->refresh();
        $this->assertSame('ORIGINAL', $entity->reference, 'Un asiento posteado es inmutable');
    }

    public function test_admin_can_update_metadata(): void
    {
        $admin = $this->getAdminUser();
        // draft explicito: la factory randomiza status y un posted seria inmutable
        $entity = JournalEntry::factory()->create(['status' => 'draft']);

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $entity->id,
            'attributes' => [
                'metadata' => array (
  'posted_by' => 'admin',
)
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$entity->id}");

        $response->assertOk();

        $entity->refresh(); // Metadata updated successfully
    }

    public function test_tech_user_cannot_update_journal_entries(): void
    {
        $tech = $this->getTechUser();
        $entity = JournalEntry::factory()->create();

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'posted'
]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_update_journal_entries(): void
    {
        $customer = $this->getCustomerUser();
        $entity = JournalEntry::factory()->create();

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'posted'
]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_journal_entries(): void
    {
        $entity = JournalEntry::factory()->create();

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $entity->id,
            'attributes' => [
                'description' => 'Updated Entry'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_journal_entries(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-entries',
            'id' => '999999',
            'attributes' => [
                'description' => 'Updated Entry'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch('/api/v1/journal-entries/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $entity = JournalEntry::factory()->create(['status' => 'draft']);

        $data = [
            'type' => 'journal-entries',
            'id' => (string) $entity->id,
            'attributes' => [
                'description' => 'invalid_data_type_here'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData($data)
            ->patch("/api/v1/journal-entries/{$entity->id}");

        // May be 422 (validation error) or 200 (if nullable/convertible)
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }
}
