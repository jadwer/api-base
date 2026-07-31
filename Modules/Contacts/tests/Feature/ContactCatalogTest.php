<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Support\SatCatalogs;

/**
 * Endpoint de catalogos del formulario de contactos.
 *
 * Contrato central (regla 7): lo que el endpoint ofrece y lo que
 * ContactRequest acepta salen de la MISMA fuente (SatCatalogs). Estos tests
 * fijan ese contrato: si alguien vuelve a duplicar listas y divergen, o si
 * un codigo servido deja de ser aceptado al crear, esto truena.
 */
class ContactCatalogTest extends TestCase
{
    public function test_guest_cannot_get_catalogs(): void
    {
        $response = $this->getJson('/api/v1/contact-catalogs');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_gets_all_catalogs(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/contact-catalogs');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'regimenes_fiscales' => [['code', 'label']],
                'usos_cfdi' => [['code', 'label']],
                'classifications' => [['code', 'label']],
            ],
        ]);

        $data = $response->json('data');
        $this->assertCount(count(SatCatalogs::REGIMENES_FISCALES), $data['regimenes_fiscales']);
        $this->assertCount(count(SatCatalogs::USOS_CFDI), $data['usos_cfdi']);
        $this->assertCount(count(SatCatalogs::CLASSIFICATIONS), $data['classifications']);
    }

    public function test_every_served_code_is_accepted_by_contact_store(): void
    {
        $admin = $this->getAdminUser();

        $catalogs = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/contact-catalogs')
            ->json('data');

        // Crear un contacto por cada combinacion servida seria lento; basta
        // recorrer el catalogo mas largo combinando con el resto ciclicamente.
        $regimenes = array_column($catalogs['regimenes_fiscales'], 'code');
        $usos = array_column($catalogs['usos_cfdi'], 'code');
        $classifications = array_column($catalogs['classifications'], 'code');

        $total = max(count($regimenes), count($usos), count($classifications));

        for ($i = 0; $i < $total; $i++) {
            $payload = [
                'data' => [
                    'type' => 'contacts',
                    'attributes' => [
                        'contactType' => 'company',
                        'name' => "Catalogo contrato {$i}",
                        'status' => 'active',
                        'regimenFiscal' => $regimenes[$i % count($regimenes)],
                        'usoCfdi' => $usos[$i % count($usos)],
                        'classification' => $classifications[$i % count($classifications)],
                    ],
                ],
            ];

            $response = $this->actingAs($admin, 'sanctum')
                ->jsonApi()
                ->expects('contacts')
                ->withData($payload['data'])
                ->post('/api/v1/contacts');

            $response->assertCreated();
        }

        $this->assertSame($total, Contact::where('name', 'like', 'Catalogo contrato %')->count());
    }

    public function test_code_outside_catalog_is_rejected(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->withData([
                'type' => 'contacts',
                'attributes' => [
                    'contactType' => 'company',
                    'name' => 'Regimen invalido',
                    'status' => 'active',
                    // El texto completo (el input viejo y el autofill lo
                    // mandaban asi) debe seguir rechazado: solo codigos.
                    'regimenFiscal' => '601 - General de Ley Personas Morales',
                ],
            ])
            ->post('/api/v1/contacts');

        $response->assertStatus(422);
    }
}
