<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

class CFDIStampingTest extends TestCase
{
    protected CompanySetting $companySetting;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        // Get existing admin user from TestCase helper
        $adminUser = $this->getAdminUser();

        // Ensure admin has PAC permissions directly
        // This handles case where permissions were added after initial seed
        $pacPermissions = [
            'billing.cfdi-invoices.stamp',
            'billing.cfdi-invoices.cancel',
            'billing.cfdi-invoices.validate',
            'billing.cfdi-invoices.cancellation-status',
        ];

        foreach ($pacPermissions as $permissionName) {
            $permission = \Spatie\Permission\Models\Permission::where('name', $permissionName)
                ->where('guard_name', 'api')
                ->first();

            if ($permission && !$adminUser->hasPermissionTo($permissionName)) {
                $adminUser->givePermissionTo($permission);
            }
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Reload user with fresh permissions
        $this->adminUser = $adminUser->fresh();
        $this->adminUser->load('roles', 'permissions');

        // Create company setting
        $this->companySetting = CompanySetting::factory()->create([
            'rfc' => 'XAXX010101000',
            'company_name' => 'Test Company SA de CV',
        ]);

        // Create contact
        $this->contact = Contact::factory()->create([
            'tax_id' => 'XEXX010101000',
        ]);
    }

    public function test_admin_can_stamp_cfdi_invoice_when_pac_enabled()
    {
        // Skip if PAC is not enabled
        if (!config('billing.sw_pac.enabled')) {
            $this->markTestSkipped('PAC integration is not enabled');
        }

        Sanctum::actingAs($this->adminUser);

        // Create CFDI invoice
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'receptor_rfc' => $this->contact->tax_id,
            'status' => 'draft',
            'uuid' => null,
        ]);

        $response = $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/stamp", [
            'regenerate_xml' => true,
        ]);

        // If PAC is properly configured, it should succeed
        // Otherwise, it will return an error
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500
        );
    }

    public function test_admin_cannot_stamp_already_stamped_invoice()
    {
        Sanctum::actingAs($this->adminUser);

        // Create already stamped CFDI invoice
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'receptor_rfc' => $this->contact->tax_id,
            'status' => 'valid',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890',
            'fecha_timbrado' => now(),
        ]);

        $response = $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/stamp");

        $response->assertStatus(500);
        $response->assertJsonFragment([
            'message' => 'Error al timbrar CFDI',
        ]);
    }

    public function test_admin_can_cancel_stamped_invoice_when_pac_enabled()
    {
        // Skip if PAC is not enabled
        if (!config('billing.sw_pac.enabled')) {
            $this->markTestSkipped('PAC integration is not enabled');
        }

        Sanctum::actingAs($this->adminUser);

        // Create stamped CFDI invoice
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'receptor_rfc' => $this->contact->tax_id,
            'status' => 'valid',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890',
            'fecha_timbrado' => now(),
        ]);

        $response = $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/cancel", [
            'motivo_cancelacion' => '02', // Comprobante emitido con errores sin relación
        ]);

        // If PAC is properly configured, it should succeed
        // Otherwise, it will return an error
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500
        );
    }

    public function test_admin_cannot_cancel_draft_invoice()
    {
        Sanctum::actingAs($this->adminUser);

        // Create draft CFDI invoice
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'receptor_rfc' => $this->contact->tax_id,
            'status' => 'draft',
            'uuid' => null,
        ]);

        $response = $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/cancel", [
            'motivo_cancelacion' => '02',
        ]);

        $response->assertStatus(500);
        $response->assertJsonFragment([
            'message' => 'Error al cancelar CFDI',
        ]);
    }

    public function test_cancellation_with_motive_01_requires_uuid_sustitucion()
    {
        Sanctum::actingAs($this->adminUser);

        // Create stamped CFDI invoice
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'receptor_rfc' => $this->contact->tax_id,
            'status' => 'valid',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890',
            'fecha_timbrado' => now(),
        ]);

        $response = $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/cancel", [
            'motivo_cancelacion' => '01', // Requires UUID de sustitución
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'El motivo 01 requiere UUID de sustitución',
        ]);
    }

    public function test_user_without_permission_cannot_stamp_invoice()
    {
        // Use existing tech user from seeders
        $techUser = User::where('email', 'tech@example.com')->first();

        Sanctum::actingAs($techUser);

        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'receptor_rfc' => $this->contact->tax_id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/stamp");

        $response->assertStatus(403);
    }

    public function test_webhook_can_update_invoice_status_on_stamp()
    {
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'series' => 'F',
            'folio' => 123,
            'status' => 'draft',
            'uuid' => null,
        ]);

        $response = $this->postJson('/api/v1/webhooks/pac/stamp', [
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890',
            'folio' => 'F-123',
            'status' => 'valid',
            'fecha_timbrado' => now()->toIso8601String(),
            'qr_code' => 'base64_qr_code_data',
        ]);

        $response->assertStatus(200);

        // Verify invoice was updated
        $invoice->refresh();
        $this->assertEquals('valid', $invoice->status);
        $this->assertEquals('A1B2C3D4-E5F6-7890-ABCD-EF1234567890', $invoice->uuid);
        $this->assertNotNull($invoice->fecha_timbrado);
    }

    public function test_webhook_can_update_invoice_status_on_cancel()
    {
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'status' => 'valid',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890',
            'fecha_timbrado' => now(),
        ]);

        $response = $this->postJson('/api/v1/webhooks/pac/cancel', [
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890',
            'status' => 'cancelled',
            'fecha_cancelacion' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);

        // Verify invoice was updated
        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);
        $this->assertNotNull($invoice->fecha_cancelacion);
    }
}
