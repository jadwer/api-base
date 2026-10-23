<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Modules\Billing\Events\CFDICancelled;
use Modules\Billing\Mail\CFDICancellationMail;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\CFDI\CFDIStampingService;
use Modules\Billing\Services\PAC\SWPacService;
use Tests\TestCase;

/**
 * Cancelacion de CFDI con aceptacion del receptor (junta 2026-10-08, estilo
 * Bind): la solicitud deja la factura en proceso, la consulta al SAT la
 * resuelve y se avisa por correo a la empresa y al receptor.
 */
class CFDICancellationFlowTest extends TestCase
{
    /** @var array<int, array<string, mixed>> respuestas sucesivas de la consulta al SAT */
    private array $satAnswers = [];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $pac = Mockery::mock(SWPacService::class);
        $pac->shouldReceive('isEnabled')->andReturn(true);
        $pac->shouldReceive('cancel')->andReturn([
            'fecha_cancelacion' => now(),
            'pac_response' => ['status' => 'success'],
        ]);
        $pac->shouldReceive('validateWithSAT')->andReturnUsing(
            fn () => array_shift($this->satAnswers) ?? ['estado' => 'Vigente', 'estatus_cancelacion' => 'En proceso']
        );
        $this->app->instance(SWPacService::class, $pac);
    }

    private function stampedInvoice(): CFDIInvoice
    {
        $invoice = CFDIInvoice::factory()->valid()->create(['pac_response' => []]);
        $invoice->companySetting->update(['email' => 'facturacion@empresa.test']);
        $invoice->contact?->update(['email' => 'cliente@receptor.test']);

        return $invoice->fresh();
    }

    private function service(): CFDIStampingService
    {
        return $this->app->make(CFDIStampingService::class);
    }

    public function test_request_leaves_invoice_pending_and_notifies_both(): void
    {
        $invoice = $this->stampedInvoice();

        $result = $this->service()->cancel($invoice, '02');

        $this->assertSame(CFDIInvoice::STATUS_CANCEL_PENDING, $result->status);
        $this->assertSame('02', $result->motivo_cancelacion);
        $this->assertSame('En proceso', $result->cancellation_status);
        $this->assertNotNull($result->cancellation_requested_at);
        $this->assertNull($result->fecha_cancelacion);

        Mail::assertSent(CFDICancellationMail::class, fn ($m) => $m->stage === 'requested' && $m->hasTo('facturacion@empresa.test'));
        Mail::assertSent(CFDICancellationMail::class, fn ($m) => $m->stage === 'requested' && $m->hasTo('cliente@receptor.test'));
    }

    public function test_immediate_cancellation_without_acceptance(): void
    {
        Event::fake([CFDICancelled::class]);
        $this->satAnswers = [['estado' => 'Cancelado', 'estatus_cancelacion' => 'Cancelado sin aceptación']];

        $result = $this->service()->cancel($this->stampedInvoice(), '02');

        $this->assertSame('cancelled', $result->status);
        $this->assertSame('Cancelado sin aceptación', $result->cancellation_status);
        $this->assertNotNull($result->fecha_cancelacion);
        Event::assertDispatchedTimes(CFDICancelled::class, 1);
        Mail::assertSent(CFDICancellationMail::class, fn ($m) => $m->stage === 'cancelled');
    }

    public function test_command_resolves_accepted_and_rejected_cancellations(): void
    {
        Event::fake([CFDICancelled::class]);
        $accepted = $this->service()->cancel($this->stampedInvoice(), '02');
        $rejected = $this->service()->cancel($this->stampedInvoice(), '03');
        $waiting = $this->service()->cancel($this->stampedInvoice(), '02');

        // Orden del comando: nunca consultadas primero, luego por fecha de solicitud
        CFDIInvoice::whereKey([$accepted->id, $rejected->id, $waiting->id])->update(['cancellation_checked_at' => null]);
        $this->satAnswers = [
            ['estado' => 'Cancelado', 'estatus_cancelacion' => 'Cancelado con aceptación'],
            ['estado' => 'Vigente', 'estatus_cancelacion' => 'Solicitud rechazada'],
            ['estado' => 'Vigente', 'estatus_cancelacion' => 'En proceso'],
        ];

        $this->artisan('cfdi:check-cancellations')
            ->expectsOutputToContain('canceladas 1, rechazadas 1, en proceso 1')
            ->assertSuccessful();

        $this->assertSame('cancelled', $accepted->fresh()->status);
        $this->assertSame('valid', $rejected->fresh()->status);
        $this->assertSame('Solicitud rechazada', $rejected->fresh()->cancellation_status);
        $this->assertSame(CFDIInvoice::STATUS_CANCEL_PENDING, $waiting->fresh()->status);
        $this->assertNotNull($waiting->fresh()->cancellation_checked_at);

        Event::assertDispatchedTimes(CFDICancelled::class, 1);
        Mail::assertSent(CFDICancellationMail::class, fn ($m) => $m->stage === 'rejected');
    }

    public function test_pending_invoice_cannot_be_cancelled_again(): void
    {
        $pending = $this->service()->cancel($this->stampedInvoice(), '02');

        $this->expectExceptionMessage('El CFDI no puede ser cancelado');
        $this->service()->cancel($pending, '02');
    }

    public function test_status_endpoint_checks_pending_cancellation(): void
    {
        $pending = $this->service()->cancel($this->stampedInvoice(), '02');
        $this->satAnswers = [['estado' => 'Cancelado', 'estatus_cancelacion' => 'Plazo vencido']];

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->getJson("/api/v1/cfdi-invoices/{$pending->id}/cancellation-status")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_status', 'Plazo vencido');
    }

    public function test_cancel_endpoint_reports_pending_in_spanish(): void
    {
        $invoice = $this->stampedInvoice();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/cfdi-invoices/{$invoice->id}/cancel", ['motivo_cancelacion' => '02'])
            ->assertOk()
            ->assertJsonPath('data.status', CFDIInvoice::STATUS_CANCEL_PENDING)
            ->assertJsonPath('message', 'Cancelación solicitada al SAT; queda en proceso hasta su confirmación');
    }

    public function test_jsonapi_exposes_cancellation_fields(): void
    {
        $pending = $this->service()->cancel($this->stampedInvoice(), '02');

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()->expects('cfdi-invoices')
            ->get("/api/v1/cfdi-invoices/{$pending->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.status', 'cancel_pending')
            ->assertJsonPath('data.attributes.motivoCancelacion', '02')
            ->assertJsonPath('data.attributes.cancellationStatus', 'En proceso');
    }
}
