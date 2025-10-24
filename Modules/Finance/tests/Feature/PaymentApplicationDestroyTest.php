<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationDestroyTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_delete_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->delete("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('payment_applications', [
            'id' => $paymentApplication->id
        ]);
    }

    public function test_admin_can_delete_PaymentApplication_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->delete("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('payment_applications', [
            'id' => $paymentApplication->id
        ]);
    }

    public function test_can_delete_inactive_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->delete("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('payment_applications', [
            'id' => $paymentApplication->id
        ]);
    }


    public function test_customer_user_cannot_delete_PaymentApplication(): void
    {
        $customer = $this->getCustomerUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->delete("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('payment_applications', [
            'id' => $paymentApplication->id
        ]);
    }

    public function test_guest_cannot_delete_PaymentApplication(): void
    {
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-applications')
            ->delete("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('payment_applications', [
            'id' => $paymentApplication->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->delete('/api/v1/payment-applications/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->delete("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->delete("/api/v1/payment-applications/{$paymentApplication->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->delete("/api/v1/payment-applications/{$paymentApplication->id}");

        $response2->assertStatus(404);
    }
}
