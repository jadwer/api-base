<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\Journal;

class JournalShowTest extends TestCase
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

    public function test_admin_can_view_Journal(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get("/api/v1/journals/{$journal->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'code',
                        'name',
                        'description',
                        'prefix',
                        'type',
                        'status',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_Journal_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $journal = Journal::factory()->create(['code' => 'TEST123', 'name' => 'Test Name', 'description' => 'test description', 'prefix' => 'test string', 'type' => 'test string', 'status' => 'active', 'metadata' => 'test value']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get("/api/v1/journals/{$journal->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'code',
                        'name',
                        'description',
                        'prefix',
                        'type',
                        'status',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_Journal_with_permission(): void
    {
        $tech = $this->getTechUser();
        $journal = Journal::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get("/api/v1/journals/{$journal->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_Journal(): void
    {
        $customer = $this->getCustomerUser();
        $journal = Journal::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get("/api/v1/journals/{$journal->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_Journal(): void
    {
        $journal = Journal::factory()->create();

        $response = $this->jsonApi()
            ->expects('journals')
            ->get("/api/v1/journals/{$journal->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_Journal(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get('/api/v1/journals/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $journal = Journal::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journals')
            ->get("/api/v1/journals/{$journal->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
