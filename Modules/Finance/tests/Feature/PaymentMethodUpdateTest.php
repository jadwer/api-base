<?php
namespace Modules\Finance\Tests\Feature;
use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentMethod;

class PaymentMethodUpdateTest extends TestCase
{
    protected function getAdminUser(): User { return User::where('email', 'admin@example.com')->firstOrFail(); }
    protected function getCustomerUser(): User { return User::where('email', 'customer@example.com')->firstOrFail(); }

    public function test_admin_can_update_PaymentMethod(): void
    {
        $pm = PaymentMethod::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-methods')->withData([
            'type' => 'payment-methods', 'id' => (string) $pm->id,
            'attributes' => ['name' => 'Updated', 'isActive' => false]
        ])->patch("/api/v1/payment-methods/{$pm->id}")->assertOk();
    }

    public function test_admin_can_partially_update_PaymentMethod(): void
    {
        $pm = PaymentMethod::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-methods')->withData([
            'type' => 'payment-methods', 'id' => (string) $pm->id, 'attributes' => ['name' => 'Partial']
        ])->patch("/api/v1/payment-methods/{$pm->id}")->assertOk();
    }

    public function test_admin_can_update_PaymentMethod_metadata(): void
    {
        $pm = PaymentMethod::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-methods')->withData([
            'type' => 'payment-methods', 'id' => (string) $pm->id, 'attributes' => ['metadata' => ['test' => 'val']]
        ])->patch("/api/v1/payment-methods/{$pm->id}")->assertOk();
    }

    public function test_customer_user_cannot_update_PaymentMethod(): void
    {
        $pm = PaymentMethod::factory()->create();
        $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('payment-methods')->withData([
            'type' => 'payment-methods', 'id' => (string) $pm->id, 'attributes' => ['name' => 'Unauth']
        ])->patch("/api/v1/payment-methods/{$pm->id}")->assertStatus(403);
    }

    public function test_guest_cannot_update_PaymentMethod(): void
    {
        $pm = PaymentMethod::factory()->create();
        $this->jsonApi()->expects('payment-methods')->withData([
            'type' => 'payment-methods', 'id' => (string) $pm->id, 'attributes' => ['name' => 'Guest']
        ])->patch("/api/v1/payment-methods/{$pm->id}")->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_PaymentMethod(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-methods')->withData([
            'type' => 'payment-methods', 'id' => '999999', 'attributes' => ['name' => 'None']
        ])->patch('/api/v1/payment-methods/999999')->assertStatus(404);
    }

    public function test_cannot_update_PaymentMethod_with_invalid_data(): void
    {
        $pm = PaymentMethod::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-methods')->withData([
            'type' => 'payment-methods', 'id' => (string) $pm->id,
            'attributes' => ['name' => '', 'isActive' => 'bad']
        ])->patch("/api/v1/payment-methods/{$pm->id}")->assertStatus(422);
    }
}
