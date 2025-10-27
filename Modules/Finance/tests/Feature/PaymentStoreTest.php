<?php
namespace Modules\Finance\Tests\Feature;
use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\{Payment, BankAccount, PaymentMethod};
use Modules\Contacts\Models\Contact;

class PaymentStoreTest extends TestCase
{
    protected function getAdminUser(): User { return User::where('email', 'admin@example.com')->firstOrFail(); }
    protected function getCustomerUser(): User { return User::where('email', 'customer@example.com')->firstOrFail(); }

    public function test_admin_can_create_Payment(): void
    {
        $contact = Contact::factory()->create(['is_customer' => true]);
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments',
            'attributes' => ['contactId' => $contact->id, 'bankAccountId' => $bankAccount->id, 'paymentMethodId' => $paymentMethod->id, 'amount' => 99.99, 'paymentNumber' => 'PAY-' . time(), 'paymentDate' => '2024-01-01', 'isActive' => true]
        ])->post('/api/v1/payments')->assertCreated();
    }

    public function test_admin_can_create_Payment_with_minimal_data(): void
    {
        $contact = Contact::factory()->create(['is_customer' => true]);
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments',
            'attributes' => ['contactId' => $contact->id, 'bankAccountId' => $bankAccount->id, 'paymentMethodId' => $paymentMethod->id, 'amount' => 100.00, 'paymentNumber' => 'PAY-' . time(), 'paymentDate' => '2024-01-01', 'isActive' => true]
        ])->post('/api/v1/payments')->assertCreated();
    }

    public function test_customer_user_cannot_create_Payment(): void
    {
        $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'attributes' => ['amount' => 100.00, 'isActive' => true]
        ])->post('/api/v1/payments')->assertStatus(403);
    }

    public function test_guest_cannot_create_Payment(): void
    {
        $this->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'attributes' => ['amount' => 100.00, 'isActive' => true]
        ])->post('/api/v1/payments')->assertStatus(401);
    }

    public function test_cannot_create_Payment_without_required_fields(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'attributes' => ['amount' => 100.00]
        ])->post('/api/v1/payments')->assertStatus(500);
    }

    public function test_cannot_create_Payment_with_invalid_data(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payments')->withData([
            'type' => 'payments', 'attributes' => ['amount' => 'not_numeric', 'isActive' => 'not_boolean']
        ])->post('/api/v1/payments')->assertStatus(422);
    }
}
