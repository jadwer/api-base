<?php

namespace Modules\Contacts\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\ContactAddress;
use Tests\TestCase;

/**
 * Campos SAT del domicilio (calle, numeros, colonia, municipio, referencia)
 * y regla de UNA sola direccion fiscal por contacto (cliente 2026-08-25).
 */
class ContactAddressSatFieldsTest extends TestCase
{
    public function test_admin_can_create_address_with_sat_fields(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData([
                'type' => 'contact-addresses',
                'attributes' => [
                    'contactId' => $contact->id,
                    'addressType' => 'fiscal',
                    'street' => 'Av. Insurgentes Sur',
                    'exteriorNumber' => '1234',
                    'interiorNumber' => '5B',
                    'neighborhood' => 'Del Valle',
                    'municipality' => 'Benito Juárez',
                    'city' => 'Ciudad de México',
                    'state' => 'Ciudad de México',
                    'country' => 'MX',
                    'postalCode' => '03100',
                    'reference' => 'Frente a una escuela',
                ],
            ])
            ->post('/api/v1/contact-addresses');

        $response->assertCreated();

        $this->assertDatabaseHas('contact_addresses', [
            'contact_id' => $contact->id,
            'address_type' => 'fiscal',
            'street' => 'Av. Insurgentes Sur',
            'exterior_number' => '1234',
            'interior_number' => '5B',
            'neighborhood' => 'Del Valle',
            'municipality' => 'Benito Juárez',
            'reference' => 'Frente a una escuela',
        ]);
    }

    public function test_mz_lt_addresses_are_accepted_as_free_text(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        // El SAT no tiene campos manzana/lote: van como texto libre
        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData([
                'type' => 'contact-addresses',
                'attributes' => [
                    'contactId' => $contact->id,
                    'addressType' => 'shipping',
                    'street' => 'Andador 605',
                    'exteriorNumber' => 'Mz 3 Lt 3',
                    'interiorNumber' => 'Int 103',
                    'postalCode' => '07979',
                ],
            ])
            ->post('/api/v1/contact-addresses')
            ->assertCreated();

        $this->assertDatabaseHas('contact_addresses', [
            'contact_id' => $contact->id,
            'exterior_number' => 'Mz 3 Lt 3',
            'interior_number' => 'Int 103',
        ]);
    }

    public function test_second_fiscal_address_for_same_contact_is_rejected(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();
        ContactAddress::factory()->create([
            'contact_id' => $contact->id,
            'address_type' => 'fiscal',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData([
                'type' => 'contact-addresses',
                'attributes' => [
                    'contactId' => $contact->id,
                    'addressType' => 'fiscal',
                    'street' => 'Otra fiscal',
                ],
            ])
            ->post('/api/v1/contact-addresses')
            ->assertStatus(422);

        $this->assertSame(1, ContactAddress::where('contact_id', $contact->id)->where('address_type', 'fiscal')->count());
    }

    public function test_fiscal_address_of_another_contact_does_not_block(): void
    {
        $admin = $this->getAdminUser();
        $contactA = Contact::factory()->create();
        $contactB = Contact::factory()->create();
        ContactAddress::factory()->create([
            'contact_id' => $contactA->id,
            'address_type' => 'fiscal',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData([
                'type' => 'contact-addresses',
                'attributes' => [
                    'contactId' => $contactB->id,
                    'addressType' => 'fiscal',
                    'street' => 'Fiscal del contacto B',
                ],
            ])
            ->post('/api/v1/contact-addresses')
            ->assertCreated();
    }

    public function test_updating_the_existing_fiscal_address_does_not_conflict_with_itself(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();
        $address = ContactAddress::factory()->create([
            'contact_id' => $contact->id,
            'address_type' => 'fiscal',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData([
                'type' => 'contact-addresses',
                'id' => (string) $address->id,
                'attributes' => [
                    'addressType' => 'fiscal',
                    'street' => 'Calle corregida',
                ],
            ])
            ->patch("/api/v1/contact-addresses/{$address->id}")
            ->assertSuccessful();

        $this->assertDatabaseHas('contact_addresses', [
            'id' => $address->id,
            'street' => 'Calle corregida',
        ]);
    }

    public function test_invalid_address_type_is_rejected(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData([
                'type' => 'contact-addresses',
                'attributes' => [
                    'contactId' => $contact->id,
                    'addressType' => 'bodega',
                ],
            ])
            ->post('/api/v1/contact-addresses')
            ->assertStatus(422);
    }

    public function test_legacy_addresses_without_sat_fields_still_work(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->withData([
                'type' => 'contact-addresses',
                'attributes' => [
                    'contactId' => $contact->id,
                    'addressType' => 'billing',
                    'addressLine1' => 'Calle Falsa 123',
                    'city' => 'Ciudad de México',
                    'postalCode' => '12345',
                ],
            ])
            ->post('/api/v1/contact-addresses')
            ->assertCreated();
    }
}
