<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyIndexTest extends TestCase
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

    public function test_admin_can_list_IdempotencyKeys(): void
    {
        $admin = $this->getAdminUser();
        
        IdempotencyKey::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get('/api/v1/idempotency-keys');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'companyId',
                        'userId',
                        'endpoint',
                        'idempotencyKey',
                        'requestHash',
                        'responseData',
                        'status',
                        'expiresAt',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_IdempotencyKeys_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        IdempotencyKey::factory()->create(['status' => 'active']);
        IdempotencyKey::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get('/api/v1/idempotency-keys?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_IdempotencyKeys_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        IdempotencyKey::factory()->create(['status' => 'active']);
        IdempotencyKey::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get('/api/v1/idempotency-keys?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_IdempotencyKeys_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get('/api/v1/idempotency-keys');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_IdempotencyKeys(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get('/api/v1/idempotency-keys');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_IdempotencyKeys(): void
    {
        $response = $this->jsonApi()
            ->expects('idempotency-keys')
            ->get('/api/v1/idempotency-keys');

        $response->assertStatus(401);
    }

    public function test_can_paginate_IdempotencyKeys(): void
    {
        $admin = $this->getAdminUser();
        
        IdempotencyKey::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get('/api/v1/idempotency-keys?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
