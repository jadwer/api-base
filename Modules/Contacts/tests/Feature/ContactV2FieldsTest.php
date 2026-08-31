<?php

namespace Modules\Contacts\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Contactos v2: extension telefonica, acceso al portal (hasPortalUser) y
 * barrido del ContactResource (el Resource manual pisa al Schema; los
 * campos comerciales/fiscales WS5/WS7 faltaban y la edicion los mostraba
 * vacios aunque estuvieran guardados).
 */
class ContactV2FieldsTest extends TestCase
{
    public function test_phone_extension_is_saved_and_returned(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData([
                'type' => 'contacts',
                'attributes' => [
                    'contactType' => 'company',
                    'name' => 'Empresa Con Extension',
                    'status' => 'active',
                    'isCustomer' => true,
                    'phone' => '5555551234',
                    'phoneExtension' => '104',
                ],
            ])
            ->post('/api/v1/contacts');

        $response->assertCreated();
        $this->assertSame('104', $response->json('data.attributes.phoneExtension'));
        $this->assertDatabaseHas('contacts', [
            'name' => 'Empresa Con Extension',
            'phone_extension' => '104',
        ]);
    }

    public function test_phone_extension_longer_than_10_is_rejected(): void
    {
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData([
                'type' => 'contacts',
                'attributes' => [
                    'contactType' => 'company',
                    'name' => 'Empresa Ext Larga',
                    'status' => 'active',
                    'isCustomer' => true,
                    'phoneExtension' => '12345678901',
                ],
            ])
            ->post('/api/v1/contacts')
            ->assertStatus(422);
    }

    public function test_has_portal_user_reflects_user_with_same_email(): void
    {
        $admin = $this->getAdminUser();

        $withUser = Contact::factory()->create(['email' => 'portal@test.com', 'is_customer' => true]);
        $withoutUser = Contact::factory()->create(['email' => 'sinportal@test.com', 'is_customer' => true]);
        User::factory()->create(['email' => 'portal@test.com']);

        $on = $this->actingAs($admin, 'sanctum')->jsonApi()->expects('contacts')
            ->get("/api/v1/contacts/{$withUser->id}");
        $off = $this->actingAs($admin, 'sanctum')->jsonApi()->expects('contacts')
            ->get("/api/v1/contacts/{$withoutUser->id}");

        $this->assertTrue($on->json('data.attributes.hasPortalUser'));
        $this->assertFalse($off->json('data.attributes.hasPortalUser'));
    }

    public function test_prospect_without_roles_can_be_created(): void
    {
        $admin = $this->getAdminUser();

        // El backend nunca exigio roles; este test fija ese contrato porque
        // el frontend viejo SI lo impedia (bug de UI corregido en v2).
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData([
                'type' => 'contacts',
                'attributes' => [
                    'contactType' => 'person',
                    'name' => 'Prospecto Puro',
                    'status' => 'active',
                    'isCustomer' => false,
                    'isSupplier' => false,
                ],
            ])
            ->post('/api/v1/contacts');

        $response->assertCreated();
        $this->assertFalse($response->json('data.attributes.isCustomer'));
        $this->assertFalse($response->json('data.attributes.isSupplier'));
    }

    public function test_resource_exposes_commercial_and_fiscal_fields(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create([
            'is_customer' => true,
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G03',
            'credit_months' => 2,
            'cuenta_contable' => '105-01-001',
            'discount_pct' => 5,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->jsonApi()->expects('contacts')
            ->get("/api/v1/contacts/{$contact->id}");

        $attrs = $response->json('data.attributes');
        $this->assertSame('601', $attrs['regimenFiscal']);
        $this->assertSame('G03', $attrs['usoCfdi']);
        $this->assertEquals(2, $attrs['creditMonths']);
        $this->assertSame('105-01-001', $attrs['cuentaContable']);
        $this->assertEquals(5, $attrs['discountPct']);
        $this->assertArrayHasKey('hasPortalUser', $attrs);
    }
}
