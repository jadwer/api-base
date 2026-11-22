<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Notification;
use Modules\Auth\Notifications\VerifyEmailNotification;
use Modules\Auth\Support\EmailVerificationToken;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Huecos SEG-02 a SEG-05 de la revision del 22-11: enlace de verificacion de correo sin
 * firma, presupuestos visibles para cualquier sesion, producto interno consultable por id
 * y estados de la orden de compra escribibles.
 */
class Seguridad20261122Test extends TestCase
{
    private function unverifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => null, 'status' => 'inactive']);
    }

    public function test_the_old_sha1_link_no_longer_verifies_an_account(): void
    {
        Notification::fake();
        $user = $this->unverifiedUser();

        $this->getJson("/api/auth/email/verify/{$user->id}/" . sha1($user->email))->assertForbidden();

        $user->refresh();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('inactive', $user->status);
        // No se deja al usuario sin salida: recibe un enlace nuevo.
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_a_signed_link_verifies_and_one_signed_for_another_user_does_not(): void
    {
        $user = $this->unverifiedUser();
        $other = $this->unverifiedUser();

        $this->getJson("/api/auth/email/verify/{$other->id}/" . EmailVerificationToken::make($user))->assertForbidden();
        $this->assertNull($other->fresh()->email_verified_at);

        $this->getJson("/api/auth/email/verify/{$user->id}/" . EmailVerificationToken::make($user))->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_an_expired_link_does_not_verify_and_sends_a_new_one(): void
    {
        Notification::fake();
        $user = $this->unverifiedUser();
        $expired = EmailVerificationToken::make($user, now()->subMinute()->getTimestamp());

        $this->getJson("/api/auth/email/verify/{$user->id}/{$expired}")
            ->assertForbidden()
            ->assertJsonPath('message', 'El enlace de verificación venció. Te enviamos uno nuevo a tu correo.');

        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_a_tampered_expiry_is_rejected(): void
    {
        Notification::fake();
        $user = $this->unverifiedUser();
        [, $signature] = explode('.', EmailVerificationToken::make($user, now()->subDay()->getTimestamp()), 2);
        $forged = base_convert((string) now()->addYear()->getTimestamp(), 10, 36) . '.' . $signature;

        $this->getJson("/api/auth/email/verify/{$user->id}/{$forged}")->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_customer_cannot_read_budget_summaries(): void
    {
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/budgets/summary')
            ->assertForbidden()
            ->assertJsonPath('errors.0.detail', 'No tiene permisos para consultar presupuestos.');
        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/budgets/needs-attention')->assertForbidden();

        $this->actingAs($this->getAdminUser(), 'sanctum')->getJson('/api/v1/budgets/summary')->assertOk();
    }

    public function test_internal_or_inactive_products_are_not_served_by_id_in_the_public_catalog(): void
    {
        $visible = Product::factory()->create(['is_active' => true, 'is_public' => true]);
        $visible->brand?->update(['is_active' => true]);
        $visible->category?->update(['is_active' => true]);
        $internal = Product::factory()->create(['is_active' => true, 'is_public' => false]);
        $inactive = Product::factory()->create(['is_active' => false, 'is_public' => true]);

        $this->jsonApi()->expects('public-products')->get("/api/public/v1/public-products/{$visible->id}")->assertOk();
        $this->assertContains($this->jsonApi()->expects('public-products')->get("/api/public/v1/public-products/{$internal->id}")->status(), [401, 403, 404]);
        $this->assertContains($this->jsonApi()->expects('public-products')->get("/api/public/v1/public-products/{$inactive->id}")->status(), [401, 403, 404]);
    }

    public function test_purchase_order_financial_states_are_not_writable(): void
    {
        $order = PurchaseOrder::factory()->create(['status' => 'pending']);
        $before = $order->fresh()->only(['invoicing_status', 'financial_status']);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('purchase-orders')
            ->withData(['type' => 'purchase-orders', 'id' => (string) $order->id, 'attributes' => [
                'notes' => 'Nota de prueba',
                'invoicingStatus' => 'cualquier_cosa',
                'financialStatus' => 'paid',
            ]])
            ->patch("/api/v1/purchase-orders/{$order->id}")
            ->assertOk();

        $order->refresh();
        $this->assertSame('Nota de prueba', $order->notes);
        $this->assertSame($before, $order->only(['invoicing_status', 'financial_status']));
    }

    public function test_customer_cannot_read_aging_reports(): void
    {
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/reports/aging-ar')->assertForbidden();
        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/reports/aging-ap')->assertForbidden();

        $this->actingAs($this->getAdminUser(), 'sanctum')->getJson('/api/v1/reports/aging-ar')->assertOk();
    }

    public function test_customer_cannot_download_or_upload_contact_documents(): void
    {
        $document = \Modules\Contacts\Models\ContactDocument::factory()->create();
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')->getJson("/api/v1/contact-documents/{$document->id}/download")
            ->assertForbidden()
            ->assertJsonPath('error', 'No tiene permisos para consultar documentos de contactos.');
        $this->actingAs($customer, 'sanctum')->getJson("/api/v1/contact-documents/{$document->id}/view")->assertForbidden();
        $this->actingAs($customer, 'sanctum')->postJson('/api/v1/contact-documents/upload', [])->assertForbidden();
    }
}
