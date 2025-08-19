<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APPayment;

class APPaymentDestroyTest extends TestCase
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

    public function test_admin_can_delete_APPayment(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->delete("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('ap_payments', [
            'id' => $aPPayment->id
        ]);
    }

    public function test_admin_can_delete_APPayment_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->delete("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('ap_payments', [
            'id' => $aPPayment->id
        ]);
    }

    public function test_can_delete_inactive_APPayment(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->delete("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('ap_payments', [
            'id' => $aPPayment->id
        ]);
    }

    public function test_customer_user_cannot_delete_APPayment(): void
    {
        $customer = $this->getCustomerUser();
        $aPPayment = APPayment::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->delete("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('ap_payments', [
            'id' => $aPPayment->id
        ]);
    }

    public function test_guest_cannot_delete_APPayment(): void
    {
        $aPPayment = APPayment::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-p-payments')
            ->delete("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('ap_payments', [
            'id' => $aPPayment->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_APPayment(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->delete('/api/v1/a-p-payments/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->delete("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->delete("/api/v1/a-p-payments/{$aPPayment->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->delete("/api/v1/a-p-payments/{$aPPayment->id}");

        $response2->assertStatus(404);
    }
}
