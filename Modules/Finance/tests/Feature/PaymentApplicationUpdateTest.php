<?php
namespace Modules\Finance\Tests\Feature;
use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationUpdateTest extends TestCase
{
    protected function getAdminUser(): User { return User::where('email', 'admin@example.com')->firstOrFail(); }
    protected function getCustomerUser(): User { return User::where('email', 'customer@example.com')->firstOrFail(); }

    public function test_admin_can_update_PaymentApplication(): void
    {
        $paymentApp = PaymentApplication::factory()->create();
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'id' => (string) $paymentApp->id,
            'attributes' => ['amount' => 199.99, 'isActive' => false]
        ])->patch("/api/v1/payment-applications/{$paymentApp->id}");
        $response->assertOk();
    }

    public function test_admin_can_partially_update_PaymentApplication(): void
    {
        $paymentApp = PaymentApplication::factory()->create(['notes' => 'Original Notes', 'amount' => 1000.00]);
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'id' => (string) $paymentApp->id,
            'attributes' => ['amount' => 1500.00]
        ])->patch("/api/v1/payment-applications/{$paymentApp->id}");
        $response->assertOk();
        $this->assertDatabaseHas('payment_applications', ['id' => $paymentApp->id, 'notes' => 'Original Notes']);
    }

    public function test_admin_can_update_PaymentApplication_metadata(): void
    {
        $paymentApp = PaymentApplication::factory()->create();
        $metadata = ['updated' => 'value', 'tags' => ['important']];
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'id' => (string) $paymentApp->id,
            'attributes' => ['metadata' => $metadata]
        ])->patch("/api/v1/payment-applications/{$paymentApp->id}");
        $response->assertOk();
        $this->assertEquals($metadata, $paymentApp->fresh()->metadata);
    }

    public function test_customer_user_cannot_update_PaymentApplication(): void
    {
        $paymentApp = PaymentApplication::factory()->create();
        $response = $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'id' => (string) $paymentApp->id, 'attributes' => ['amount' => 999.99]
        ])->patch("/api/v1/payment-applications/{$paymentApp->id}");
        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_PaymentApplication(): void
    {
        $paymentApp = PaymentApplication::factory()->create();
        $response = $this->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'id' => (string) $paymentApp->id, 'attributes' => ['amount' => 999.99]
        ])->patch("/api/v1/payment-applications/{$paymentApp->id}");
        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_PaymentApplication(): void
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'id' => '999999', 'attributes' => ['amount' => 999.99]
        ])->patch('/api/v1/payment-applications/999999');
        $response->assertStatus(404);
    }

    public function test_cannot_update_PaymentApplication_with_invalid_data(): void
    {
        $paymentApp = PaymentApplication::factory()->create();
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'id' => (string) $paymentApp->id,
            'attributes' => ['amount' => 'not_numeric', 'isActive' => 'invalid_boolean']
        ])->patch("/api/v1/payment-applications/{$paymentApp->id}");
        $response->assertStatus(422);
    }
}
