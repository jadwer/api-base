<?php

namespace Modules\Contacts\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Tests\TestCase;

/**
 * Varios correos y telefonos por contacto (peticion de Jasim, 2026-09-30).
 */
class ContactChannelsTest extends TestCase
{
    private function store(array $attributes)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData([
                'type' => 'contacts',
                'attributes' => array_merge([
                    'contactType' => 'company',
                    'name' => 'Laboratorios Canales',
                    'status' => 'active',
                    'isCustomer' => true,
                ], $attributes),
            ])
            ->post('/api/v1/contacts');
    }

    public function test_primary_email_rejects_more_than_one_address(): void
    {
        foreach (['compras@lab.mx, lab@lab.mx', 'compras@lab.mx;lab@lab.mx', 'compras@lab.mx lab@lab.mx', 'compras@lab@lab.mx'] as $email) {
            $response = $this->store(['email' => $email]);
            $response->assertStatus(422);
            $this->assertStringContainsString('un solo correo principal', json_encode($response->json('errors'), JSON_UNESCAPED_UNICODE), $email);
            $this->assertSame('/data/attributes/email', $response->json('errors.0.source.pointer'));
        }
    }

    public function test_additional_emails_are_normalized_and_exclude_the_primary(): void
    {
        $response = $this->store([
            'email' => 'compras@lab.mx',
            'additionalEmails' => ['Laboratorio@Lab.mx', ' inventarios@lab.mx ', 'laboratorio@lab.mx', 'COMPRAS@lab.mx'],
        ]);

        $response->assertCreated();
        $this->assertSame(['laboratorio@lab.mx', 'inventarios@lab.mx'], $response->json('data.attributes.additionalEmails'));
    }

    public function test_additional_email_with_comma_is_rejected(): void
    {
        $this->store(['additionalEmails' => ['a@lab.mx, b@lab.mx']])->assertStatus(422);
    }

    public function test_phones_are_cleaned_and_first_one_is_mirrored(): void
    {
        $response = $this->store([
            'phones' => [
                ['label' => 'Matriz', 'code' => '+52', 'number' => '55 1666-6344', 'ext' => 'ext 2213'],
                ['label' => 'Suc. Zapopan', 'number' => '(33) 5534-4512'],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame([
            ['label' => 'Matriz', 'code' => '52', 'number' => '5516666344', 'ext' => '2213'],
            ['label' => 'Suc. Zapopan', 'code' => '52', 'number' => '3355344512', 'ext' => null],
        ], $response->json('data.attributes.phones'));
        $this->assertSame('+52 5516666344', $response->json('data.attributes.phone'));
        $this->assertSame('2213', $response->json('data.attributes.phoneExtension'));
    }

    public function test_mexican_number_needs_ten_digits_and_foreign_up_to_twelve(): void
    {
        $this->store(['phones' => [['number' => '33553445']]])->assertStatus(422);
        $this->store(['phones' => [['code' => '1', 'number' => '1234567890123']]])->assertStatus(422);
        $this->store(['phones' => [['code' => '1', 'number' => '2025550143']]])->assertCreated();
    }

    public function test_legacy_contact_with_odd_phone_can_still_be_edited(): void
    {
        $contact = Contact::factory()->create(['phone' => '123', 'email' => 'viejo@lab.mx']);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData(['type' => 'contacts', 'id' => (string) $contact->id, 'attributes' => ['name' => 'Nombre nuevo']])
            ->patch("/api/v1/contacts/{$contact->id}")
            ->assertOk();

        $this->assertSame('Nombre nuevo', $contact->fresh()->name);
    }

    public function test_legacy_foreign_phone_keeps_its_country_code(): void
    {
        $contact = Contact::factory()->create(['phone' => '+1-231-796-0300']);

        $this->assertSame([['label' => 'Principal', 'code' => '1', 'number' => '2317960300', 'ext' => null]], $contact->fresh()->phones);
    }

    public function test_legacy_phone_write_builds_the_list(): void
    {
        $contact = Contact::factory()->create(['phone' => '+52 (55) 1234 5678', 'phone_extension' => '15']);

        $this->assertSame([['label' => 'Principal', 'code' => '52', 'number' => '5512345678', 'ext' => '15']], $contact->fresh()->phones);
    }
}
