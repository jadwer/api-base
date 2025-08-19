<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\JournalLine;

class JournalLineUpdateTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_update_JournalLine(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create();

        $data = [
            'type' => 'journal-lines',
            'id' => (string) $journalLine->id,
            'attributes' => [
                'name' => 'Updated JournalLine',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->patch("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('journal_lines', [
            'id' => $journalLine->id,
            'name' => 'Updated JournalLine',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_JournalLine(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'journal-lines',
            'id' => (string) $journalLine->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->patch("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('journal_lines', [
            'id' => $journalLine->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_JournalLine_metadata(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'journal-lines',
            'id' => (string) $journalLine->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->patch("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertOk();
        
        $journalLine->refresh();
        $this->assertEquals($metadata, $journalLine->metadata);
    }

    public function test_customer_user_cannot_update_JournalLine(): void
    {
        $customer = $this->getCustomerUser();
        $journalLine = JournalLine::factory()->create();

        $data = [
            'type' => 'journal-lines',
            'id' => (string) $journalLine->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->patch("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_JournalLine(): void
    {
        $journalLine = JournalLine::factory()->create();

        $data = [
            'type' => 'journal-lines',
            'id' => (string) $journalLine->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->patch("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_JournalLine(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'journal-lines',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->patch('/api/v1/journal-lines/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_JournalLine_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $journalLine = JournalLine::factory()->create();

        $data = [
            'type' => 'journal-lines',
            'id' => (string) $journalLine->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData($data)
            ->patch("/api/v1/journal-lines/{$journalLine->id}");

        $response->assertStatus(422);
    }
}
