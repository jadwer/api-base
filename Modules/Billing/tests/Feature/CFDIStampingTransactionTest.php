<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Modules\Billing\Events\CFDICancelled;
use Modules\Billing\Events\CFDIStamped;
use Modules\Billing\Exceptions\PacException;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Services\CFDI\CFDIPDFGenerator;
use Modules\Billing\Services\CFDI\CFDIStampingService;
use Modules\Billing\Services\CFDI\CFDIXMLGenerator;
use Modules\Billing\Services\PAC\SWPacService;
use Modules\Contacts\Models\Contact;
use Tests\TestCase;

/**
 * B2 paquete C: el servicio de timbrado no debe revertir el trabajo del
 * llamador ni deshacer un timbrado ya confirmado (antes hacia rollBack
 * despues de commit).
 */
class CFDIStampingTransactionTest extends TestCase
{
    private const UUID = '11111111-2222-3333-4444-555555555555';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function service(SWPacService $pac): CFDIStampingService
    {
        $xml = Mockery::mock(CFDIXMLGenerator::class);
        $xml->shouldReceive('generate')->andReturn('<cfdi:Comprobante/>');

        $pdf = Mockery::mock(CFDIPDFGenerator::class);
        $pdf->shouldReceive('generate')->andReturn('cfdi/pdf/test.pdf');

        return new CFDIStampingService($pac, $xml, $pdf);
    }

    private function draftInvoice(): CFDIInvoice
    {
        $invoice = CFDIInvoice::factory()->draft()->create(['xml_original' => null]);
        CFDIItem::factory()->create(['cfdi_invoice_id' => $invoice->id]);

        return $invoice->fresh();
    }

    private function pacThatStamps(): SWPacService
    {
        $pac = Mockery::mock(SWPacService::class);
        $pac->shouldReceive('isEnabled')->andReturn(true);
        $pac->shouldReceive('stamp')->andReturn([
            'uuid' => self::UUID,
            'fecha_timbrado' => now(),
            'xml_timbrado' => '<cfdi:Comprobante timbrado="1"/>',
            'qr_code' => null,
            'pac_response' => ['ok' => true],
        ]);

        return $pac;
    }

    public function test_listener_failure_after_stamp_keeps_invoice_and_caller_work(): void
    {
        $invoice = $this->draftInvoice();
        Event::listen(CFDIStamped::class, function () {
            throw new \RuntimeException('listener roto');
        });

        $level = DB::transactionLevel();
        DB::beginTransaction();
        $marker = Contact::factory()->create();

        $result = $this->service($this->pacThatStamps())->stamp($invoice);

        $this->assertSame($level + 1, DB::transactionLevel(), 'el servicio no debe cerrar la transaccion del llamador');
        DB::commit();

        $this->assertSame('valid', $result->status);
        $fresh = $invoice->fresh();
        $this->assertSame('valid', $fresh->status);
        $this->assertSame(self::UUID, $fresh->uuid);
        $this->assertNotNull($fresh->xml_original);
        $this->assertDatabaseHas('contacts', ['id' => $marker->id]);
    }

    public function test_pac_failure_leaves_invoice_untouched_except_error_and_keeps_caller_work(): void
    {
        $invoice = $this->draftInvoice();
        Event::fake([CFDIStamped::class]);

        $pac = Mockery::mock(SWPacService::class);
        $pac->shouldReceive('isEnabled')->andReturn(true);
        $pac->shouldReceive('stamp')->andThrow(new PacException('CFDI40999 rechazado', 400));

        $level = DB::transactionLevel();
        DB::beginTransaction();
        $marker = Contact::factory()->create();

        try {
            $this->service($pac)->stamp($invoice, true);
            $this->fail('Se esperaba PacException');
        } catch (PacException $e) {
            $this->assertSame('CFDI40999 rechazado', $e->getMessage());
            $this->assertSame(400, $e->getStatusCode());
        }

        $this->assertSame($level + 1, DB::transactionLevel());
        DB::commit();

        $fresh = $invoice->fresh();
        $this->assertSame('draft', $fresh->status);
        $this->assertNull($fresh->uuid);
        $this->assertNull($fresh->xml_original);
        $this->assertSame('CFDI40999 rechazado', $fresh->error_message);
        $this->assertDatabaseHas('contacts', ['id' => $marker->id]);
        Event::assertNotDispatched(CFDIStamped::class);
    }

    public function test_successful_stamp_dispatches_event_once(): void
    {
        $invoice = $this->draftInvoice();
        Event::fake([CFDIStamped::class]);

        $this->service($this->pacThatStamps())->stamp($invoice);

        Event::assertDispatchedTimes(CFDIStamped::class, 1);
        $this->assertNotNull($invoice->fresh()->xml_path);
    }

    public function test_listener_failure_after_cancel_keeps_cancellation(): void
    {
        $invoice = CFDIInvoice::factory()->valid()->create(['uuid' => self::UUID, 'pac_response' => []]);
        Event::listen(CFDICancelled::class, function () {
            throw new \RuntimeException('listener roto');
        });

        $pac = Mockery::mock(SWPacService::class);
        $pac->shouldReceive('isEnabled')->andReturn(true);
        $pac->shouldReceive('cancel')->andReturn([
            'fecha_cancelacion' => now(),
            'pac_response' => ['ok' => true],
        ]);

        $level = DB::transactionLevel();
        $result = $this->service($pac)->cancel($invoice);

        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame('cancelled', $result->status);
        $this->assertSame('cancelled', $invoice->fresh()->status);
    }
}
