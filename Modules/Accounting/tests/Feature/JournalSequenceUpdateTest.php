<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceUpdateTest extends TestCase
{
    public function test_admin_can_update_JournalSequence(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'fiscalYear' => 2026,
                'currentNumber' => 100
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();

        $this->assertDatabaseHas('journal_sequences', [
            'id' => $journalSequence->id,
            'fiscal_year' => 2026,
            'current_number' => 100
        ]);
    }

    public function test_admin_can_partially_update_JournalSequence(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create([
            'fiscal_year' => 2025,
            'current_number' => 1
        ]);

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'currentNumber' => 50
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();

        $this->assertDatabaseHas('journal_sequences', [
            'id' => $journalSequence->id,
            'fiscal_year' => 2025,
            'current_number' => 1
        ]);
    }

    public function test_admin_can_update_JournalSequence_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
        ];

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertOk();

        $journalSequence->refresh();
        $this->assertEquals($metadata, $journalSequence->metadata);
    }

    public function test_customer_user_cannot_update_JournalSequence(): void
    {
        $customer = $this->getCustomerUser();
        $journalSequence = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'fiscalYear' => 2099
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_JournalSequence(): void
    {
        $journalSequence = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'fiscalYear' => 2099
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_JournalSequence(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-sequences',
            'id' => '999999',
            'attributes' => [
                'fiscalYear' => 2099
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch('/api/v1/journal-sequences/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_JournalSequence_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $journalSequence = JournalSequence::factory()->create();

        $data = [
            'type' => 'journal-sequences',
            'id' => (string) $journalSequence->id,
            'attributes' => [
                'fiscalYear' => 'invalid',
                'currentNumber' => 'invalid'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-sequences')
            ->withData($data)
            ->patch("/api/v1/journal-sequences/{$journalSequence->id}");

        $response->assertStatus(422);
    }
}
