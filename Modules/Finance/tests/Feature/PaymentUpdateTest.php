<?php
namespace Modules\Finance\Tests\Feature;
use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\Payment;

class PaymentUpdateTest extends TestCase
{
    protected function getAdminUser(): User { return User::where('email', 'admin@example.com')->firstOrFail(); }
    protected function getCustomerUser(): User { return User::where('email', 'customer@example.com')->firstOrFail(); }

    public function test_admin_can_update_Payment(): void
    {
        $payment = Payment::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'id' => (string) $payment->id, 'attributes' => ['amount' => 199.99, 'isActive' => false]
        ])->patch("/api/v1/payments/{$payment->id}")->assertOk();
    }

    public function test_admin_can_partially_update_Payment(): void
    {
        $payment = Payment::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'id' => (string) $payment->id, 'attributes' => ['amount' => 1500.00]
        ])->patch("/api/v1/payments/{$payment->id}")->assertOk();
    }

    public function test_admin_can_update_Payment_metadata(): void
    {
        $payment = Payment::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'id' => (string) $payment->id, 'attributes' => ['metadata' => ['test' => 'val']]
        ])->patch("/api/v1/payments/{$payment->id}")->assertOk();
    }

    public function test_customer_user_cannot_update_Payment(): void
    {
        $payment = Payment::factory()->create();
        $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'id' => (string) $payment->id, 'attributes' => ['amount' => 999.99]
        ])->patch("/api/v1/payments/{$payment->id}")->assertStatus(403);
    }

    public function test_guest_cannot_update_Payment(): void
    {
        $payment = Payment::factory()->create();
        $this->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'id' => (string) $payment->id, 'attributes' => ['amount' => 999.99]
        ])->patch("/api/v1/payments/{$payment->id}")->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_Payment(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'id' => '999999', 'attributes' => ['amount' => 999.99]
        ])->patch('/api/v1/payments/999999')->assertStatus(404);
    }

    public function test_cannot_update_Payment_with_invalid_data(): void
    {
        $payment = Payment::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'id' => (string) $payment->id,
            'attributes' => ['amount' => 'not_numeric', 'isActive' => 'bad']
        ])->patch("/api/v1/payments/{$payment->id}")->assertStatus(422);
    }
}
