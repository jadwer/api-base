<?php

namespace Modules\Billing\Tests\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Contacts\Models\Contact;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Fase 2.7: test de INVARIANTE de negocio (no de fachada) para el timbrado CFDI.
 *
 * Lo que fija: el status de un CFDI solo transiciona por el flujo real de timbrado
 * (CFDIStampingService via POST /cfdi-invoices/{id}/stamp). Timbrar persiste
 * uuid + xml timbrado + status valid; timbrar dos veces no genera doble timbre
 * (la segunda ni siquiera llega al PAC); un rechazo del PAC deja el CFDI SIN
 * timbrar y con el error registrado.
 *
 * Reglas anti-fachada que respeta este archivo:
 * - El CFDI draft se crea por el camino real mas barato: los endpoints JSON:API
 *   POST /cfdi-invoices + POST /cfdi-items (createFromOrder exige una orden
 *   delivered con todo el ciclo detras; innecesario para ESTE invariante).
 *   Nada de CFDIInvoice::factory().
 * - Se mockea SOLO la frontera externa: el HTTP al PAC via Http::fake(). El
 *   SWPacService, el CFDIStampingService y el generador de XML corren REALES.
 * - Asserts contra la base (cfdi_invoices), no contra el JSON de la respuesta.
 *
 * Nota: el caso "PATCH no puede mover status draft->valid" ya lo cubre
 * CFDIInvoiceUpdateTest::test_status_cannot_be_changed_via_patch. Aqui se cubre
 * la direccion inversa sobre un CFDI YA TIMBRADO (valid no regresa a draft).
 */
class CFDIStampInvariantTest extends TestCase
{
    private const PAC_URL = 'https://sw-pac.invariant.test';
    private const FAKE_UUID = 'FA83E647-2AC4-4E1F-B393-000000000001';
    private const STAMPED_XML = '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Sello="FAKE-SELLO-TIMBRADO"/>';

    protected User $admin;
    protected CompanySetting $companySetting;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        // Permisos PAC del admin (mismo patron defensivo que CFDIStampingTest).
        $admin = $this->getAdminUser();
        foreach (['billing.cfdi-invoices.stamp', 'billing.cfdi-invoices.cancel'] as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'api')
                ->first();
            if ($permission && !$admin->hasPermissionTo($permissionName)) {
                $admin->givePermissionTo($permission);
            }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = $admin->fresh();

        // Emisor determinista: el generador de XML toma el PRIMER CompanySetting
        // activo, asi que desactivamos cualquier otro sembrado.
        CompanySetting::query()->update(['is_active' => false]);
        $this->companySetting = CompanySetting::factory()->create([
            'rfc' => 'EKU9003173C9',
            'company_name' => 'ESCUELA KEMPER URGATE',
            'tax_regime' => '601',
            'postal_code' => '42501',
            'is_active' => true,
        ]);

        $this->contact = Contact::factory()->customer()->create([
            'tax_id' => 'XAXX010101000',
        ]);

        // PAC habilitado apuntando a una URL fake; el HTTP se intercepta con
        // Http::fake() (la UNICA pieza mockeada). Token fijo para que el servicio
        // no intente autenticarse. Sin reintentos para no dormir en el caso de rechazo.
        config([
            'billing.sw_pac.enabled' => true,
            'billing.sw_pac.url' => self::PAC_URL,
            'billing.sw_pac.token' => 'fake-test-token',
            'billing.sw_pac.retry_attempts' => 1,
            'billing.sw_pac.retry_delay' => 0,
            'billing.sw_pac.timeout' => 5,
        ]);

        // El XML timbrado se persiste en el disco local: aislarlo del FS real.
        Storage::fake('local');
    }

    /**
     * Crea un CFDI draft por el camino real (endpoints JSON:API), con un concepto.
     */
    private function createDraftCfdiViaApi(int $folio = 9001): CFDIInvoice
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', "Bearer {$token}")
            ->withData([
                'type' => 'cfdi-invoices',
                'attributes' => [
                    'companySettingId' => $this->companySetting->id,
                    'contactId' => $this->contact->id,
                    'series' => 'F',
                    'folio' => $folio,
                    'tipoComprobante' => 'I',
                    'receptorRfc' => 'XAXX010101000',
                    'receptorNombre' => 'PUBLICO EN GENERAL',
                    'receptorUsoCfdi' => 'G03',
                    // Importes en CENTAVOS (convencion del modulo Billing).
                    'subtotal' => 100000,
                    'iva' => 16000,
                    'total' => 116000,
                    'moneda' => 'MXN',
                    'metodoPago' => 'PUE',
                    'fechaEmision' => now()->toISOString(),
                ],
            ])
            ->post('/api/v1/cfdi-invoices');

        $response->assertCreated();
        $invoiceId = (int) $response->json('data.id');

        $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', "Bearer {$token}")
            ->withData([
                'type' => 'cfdi-items',
                'attributes' => [
                    'cfdiInvoiceId' => $invoiceId,
                    'numeroLinea' => 1,
                    'claveProdServ' => '01010101',
                    'claveUnidad' => 'E48',
                    'unidad' => 'Servicio',
                    'cantidad' => 1.0,
                    'descripcion' => 'Servicio de prueba invariante',
                    'valorUnitario' => 100000,
                    'importe' => 100000,
                    'objetoImp' => '02',
                ],
            ])
            ->post('/api/v1/cfdi-items')
            ->assertCreated();

        return CFDIInvoice::findOrFail($invoiceId);
    }

    /**
     * Http::fake de un timbrado exitoso en la frontera del PAC.
     */
    private function fakePacSuccess(): void
    {
        Http::fake([
            self::PAC_URL . '/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'uuid' => self::FAKE_UUID,
                    'fechaTimbrado' => now()->format('Y-m-d\TH:i:s'),
                    'cfdi' => base64_encode(self::STAMPED_XML),
                    'cadenaOriginalSAT' => '||1.1|' . self::FAKE_UUID . '||',
                    'selloSAT' => 'FAKE-SELLO-SAT',
                    'selloCFDI' => 'FAKE-SELLO-CFDI',
                    'noCertificadoSAT' => '30001000000400002495',
                    'qrCode' => 'FAKE-QR-BASE64',
                ],
            ], 200),
        ]);
    }

    /**
     * Invariante: timbrar persiste uuid, xml timbrado (sello incluido), fecha y
     * status valid en la BASE, y el XML queda guardado en storage.
     */
    public function test_stamping_persists_uuid_stamped_xml_and_valid_status(): void
    {
        Sanctum::actingAs($this->admin);
        $invoice = $this->createDraftCfdiViaApi(9001);
        $this->assertSame('draft', $invoice->status, 'Precondicion: el CFDI nace draft');
        $this->assertNull($invoice->uuid);

        $this->fakePacSuccess();

        $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/stamp", [
            'regenerate_xml' => true,
        ])->assertStatus(200);

        // Asserts contra la base.
        $this->assertDatabaseHas('cfdi_invoices', [
            'id' => $invoice->id,
            'status' => 'valid',
            'uuid' => self::FAKE_UUID,
        ]);

        $fresh = $invoice->fresh();
        $this->assertNotNull($fresh->fecha_timbrado, 'fecha_timbrado debe persistirse');
        $this->assertSame(self::STAMPED_XML, $fresh->xml_timbrado, 'El XML timbrado (con sello) debe persistirse');
        $this->assertNotEmpty($fresh->xml_original, 'El XML original generado debe persistirse');
        $this->assertNotEmpty($fresh->pac_response, 'La respuesta cruda del PAC debe persistirse');
        $this->assertNotNull($fresh->xml_path);
        Storage::disk('local')->assertExists($fresh->xml_path);

        // La frontera mockeada fue el endpoint de Emision de SW, exactamente una vez.
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/cfdi33/issue/v4/b64'));
    }

    /**
     * Invariante: timbrar dos veces no genera doble timbre (la segunda llamada se
     * rechaza ANTES de llegar al PAC) y un CFDI valid no regresa a draft por PATCH.
     */
    public function test_stamping_twice_does_not_double_stamp_and_valid_cannot_return_to_draft(): void
    {
        Sanctum::actingAs($this->admin);
        $invoice = $this->createDraftCfdiViaApi(9002);

        $this->fakePacSuccess();

        $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/stamp", [
            'regenerate_xml' => true,
        ])->assertStatus(200);

        $firstStamp = $invoice->fresh();

        // Segundo timbrado: rechazado, y el PAC NO recibe una segunda llamada.
        $second = $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/stamp", [
            'regenerate_xml' => true,
        ]);
        $this->assertGreaterThanOrEqual(400, $second->status(), 'El doble timbrado debe rechazarse');

        Http::assertSentCount(1); // una sola llamada al PAC en total

        // La base quedo intacta: mismo uuid, mismo xml, sigue valid.
        $this->assertDatabaseHas('cfdi_invoices', [
            'id' => $invoice->id,
            'status' => 'valid',
            'uuid' => self::FAKE_UUID,
        ]);
        $this->assertSame($firstStamp->xml_timbrado, $invoice->fresh()->xml_timbrado);

        // Un CFDI valid NO regresa a draft por PATCH (status es readOnlyOnUpdate:
        // el PATCH lo ignora; el invariante es que la base no cambia).
        $token = $this->admin->createToken('test')->plainTextToken;
        $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', "Bearer {$token}")
            ->withData([
                'type' => 'cfdi-invoices',
                'id' => (string) $invoice->id,
                'attributes' => ['status' => 'draft'],
            ])
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $this->assertDatabaseHas('cfdi_invoices', [
            'id' => $invoice->id,
            'status' => 'valid',
            'uuid' => self::FAKE_UUID,
        ]);
    }

    /**
     * Invariante: si el PAC rechaza, el CFDI NO queda valid (sigue draft, sin uuid)
     * y el error queda registrado en la base.
     */
    public function test_pac_rejection_leaves_invoice_unstamped_and_records_error(): void
    {
        Sanctum::actingAs($this->admin);
        $invoice = $this->createDraftCfdiViaApi(9003);

        Http::fake([
            self::PAC_URL . '/*' => Http::response([
                'message' => 'CFDI40108 - El sello del emisor no es valido',
                'messageDetail' => 'Rechazo simulado del PAC',
                'status' => 'error',
            ], 400),
        ]);

        $response = $this->postJson("/api/v1/cfdi-invoices/{$invoice->id}/stamp", [
            'regenerate_xml' => true,
        ]);
        $this->assertGreaterThanOrEqual(400, $response->status(), 'El rechazo del PAC no puede responder 200');

        $fresh = $invoice->fresh();

        // NO quedo timbrado: sin uuid, sin xml timbrado, status sigue draft.
        $this->assertSame('draft', $fresh->status, 'Un rechazo del PAC no puede dejar el CFDI valid');
        $this->assertNull($fresh->uuid);
        $this->assertNull($fresh->xml_timbrado);
        $this->assertNull($fresh->fecha_timbrado);

        // El error quedo registrado para diagnostico.
        $this->assertNotEmpty($fresh->error_message, 'El rechazo del PAC debe registrarse en error_message');

        // Y el PAC si fue consultado (el fallo vino de la frontera, no de un atajo interno).
        Http::assertSent(fn ($request) => str_contains($request->url(), '/cfdi33/issue/v4/b64'));
    }
}
