<?php

namespace Modules\Billing\Services\CFDI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Exceptions\PacException;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\PAC\SWPacService;
use Modules\Billing\Events\CFDIStamped;
use Modules\Billing\Events\CFDICancelled;

/**
 * CFDI Stamping Orchestration Service
 *
 * Coordinates the stamping and cancellation of CFDI invoices
 */
class CFDIStampingService
{
    protected SWPacService $pacService;
    protected CFDIXMLGenerator $xmlGenerator;
    protected CFDIPDFGenerator $pdfGenerator;

    public function __construct(
        SWPacService $pacService,
        CFDIXMLGenerator $xmlGenerator,
        CFDIPDFGenerator $pdfGenerator
    ) {
        $this->pacService = $pacService;
        $this->xmlGenerator = $xmlGenerator;
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Stamp a CFDI invoice
     *
     * @param CFDIInvoice $invoice
     * @param bool $regenerateXml Whether to regenerate XML before stamping
     * @return CFDIInvoice
     * @throws PacException
     */
    public function stamp(CFDIInvoice $invoice, bool $regenerateXml = false): CFDIInvoice
    {
        // Validate invoice can be stamped
        $this->validateForStamping($invoice);

        // Factura libre: su cuenta por cobrar se crea al timbrar; se valida
        // antes del PAC para no dejar un CFDI timbrado sin cobranza (23-10).
        $freeInvoices = app(FreeInvoiceService::class);
        if ($freeInvoices->needsReceivable($invoice)) {
            try {
                $freeInvoices->assertCanPostReceivable($invoice);
            } catch (\DomainException $e) {
                throw new PacException($e->getMessage(), 422);
            }
        }

        $mustSaveXml = $regenerateXml || empty($invoice->xml_original);

        // PAC fuera de la transaccion: es una llamada externa que no se puede revertir
        try {
            $xml = $mustSaveXml
                ? $this->xmlGenerator->generate($invoice)
                : $invoice->xml_original;

            $stampData = $this->pacService->stamp($xml);
        } catch (PacException $e) {
            $this->recordFailure($invoice, $e, 'CFDI stamping failed');
            throw $e;
        } catch (\Exception $e) {
            throw $this->unexpected($invoice, $e, 'Unexpected error during CFDI stamping', 'Error inesperado al timbrar CFDI: ');
        }

        // Solo las escrituras van en la transaccion. Si falla, revierte su
        // propio savepoint y nunca el trabajo del llamador.
        try {
            DB::transaction(function () use ($invoice, $mustSaveXml, $xml, $stampData) {
                $invoice->update(array_merge($mustSaveXml ? ['xml_original' => $xml] : [], [
                    'uuid' => $stampData['uuid'],
                    'fecha_timbrado' => $stampData['fecha_timbrado'],
                    'xml_timbrado' => $stampData['xml_timbrado'],
                    'qr_code' => $stampData['qr_code'] ?? null,
                    'pac_response' => $stampData['pac_response'],
                    'status' => 'valid',
                ]));
            });
        } catch (\Exception $e) {
            throw $this->unexpected($invoice, $e, 'Unexpected error during CFDI stamping', 'Error inesperado al timbrar CFDI: ');
        }

        Log::info('CFDI stamped successfully', [
            'invoice_id' => $invoice->id,
            'uuid' => $stampData['uuid'],
        ]);

        // Efectos secundarios tras el commit: el CFDI ya esta timbrado ante el
        // SAT, un fallo aqui se registra pero no deshace ni oculta el timbrado.
        $this->afterCommit($invoice, 'Failed to save stamped XML', fn () => $this->saveStampedXml($invoice, $stampData['xml_timbrado']));
        $this->afterCommit($invoice, 'PDF generation failed after stamping', fn () => $this->pdfGenerator->generate($invoice->fresh()));
        $this->afterCommit($invoice, 'Free invoice receivable failed', function () use ($invoice, $freeInvoices) {
            try {
                $freeInvoices->createReceivable($invoice->fresh());
            } catch (\Throwable $e) {
                // Queda a la vista en el CFDI para registrarla a mano; el timbrado no se toca
                $invoice->update(['metadata' => array_merge($invoice->fresh()->metadata ?? [], ['ar_error' => $e->getMessage()])]);
                throw $e;
            }
        });
        $this->afterCommit($invoice, 'CFDIStamped listener failed', fn () => event(new CFDIStamped($invoice->fresh())));

        return $invoice->fresh();
    }

    /**
     * Cancel a CFDI invoice
     *
     * @param CFDIInvoice $invoice
     * @param string $motivoCancelacion Cancellation reason code (01-04)
     * @param CFDIInvoice|null $invoiceSustitucion Replacement invoice (for motive 01)
     * @return CFDIInvoice
     * @throws PacException
     */
    public function cancel(
        CFDIInvoice $invoice,
        string $motivoCancelacion = '02',
        ?CFDIInvoice $invoiceSustitucion = null
    ): CFDIInvoice {
        // Validate invoice can be cancelled
        $this->validateForCancellation($invoice);

        $uuidSustitucion = null;

        try {
            // Get company settings for RFC
            $companySetting = $invoice->companySetting;
            if (!$companySetting) {
                throw new PacException('No se encontró configuración de empresa');
            }

            if ($invoiceSustitucion) {
                if (!$invoiceSustitucion->uuid) {
                    throw new PacException('El CFDI de sustitución no está timbrado');
                }
                $uuidSustitucion = $invoiceSustitucion->uuid;
            }

            // Call PAC to cancel (fuera de la transaccion)
            $cancelData = $this->pacService->cancel(
                uuid: $invoice->uuid,
                rfcEmisor: $companySetting->rfc,
                rfcReceptor: $invoice->receptor_rfc,
                total: $invoice->total / 100, // Convert from cents
                motivoCancelacion: $motivoCancelacion,
                uuidSustitucion: $uuidSustitucion
            );
        } catch (PacException $e) {
            $this->recordFailure($invoice, $e, 'CFDI cancellation failed');
            throw $e;
        } catch (\Throwable $e) {
            // el PAC declara solo PacException, pero un fallo HTTP o de tipos tambien debe llegar envuelto
            throw $this->unexpected($invoice, $e, 'Unexpected error during CFDI cancellation', 'Error inesperado al cancelar CFDI: ');
        }

        // El PAC solo recibe la solicitud: si el SAT pide aceptacion del
        // receptor, la cancelacion queda "En proceso" hasta 72 horas. La
        // factura sigue vigente hasta que la consulta al SAT diga Cancelado
        // (checkCancellation, comando cfdi:check-cancellations).
        try {
            DB::transaction(function () use ($invoice, $cancelData, $motivoCancelacion, $uuidSustitucion) {
                $invoice->update([
                    'status' => CFDIInvoice::STATUS_CANCEL_PENDING,
                    'motivo_cancelacion' => $motivoCancelacion,
                    'uuid_sustitucion' => $uuidSustitucion,
                    'cancellation_status' => 'Solicitud enviada',
                    'cancellation_requested_at' => now(),
                    'cancellation_checked_at' => null,
                    'error_message' => null,
                    'pac_response' => array_merge(
                        $invoice->pac_response ?? [],
                        ['cancellation' => $cancelData['pac_response']]
                    ),
                ]);
            });
        } catch (\Exception $e) {
            throw $this->unexpected($invoice, $e, 'Unexpected error during CFDI cancellation', 'Error inesperado al cancelar CFDI: ');
        }

        Log::info('CFDI cancellation requested', [
            'invoice_id' => $invoice->id,
            'uuid' => $invoice->uuid,
            'motive' => $motivoCancelacion,
        ]);

        $this->afterCommit($invoice, 'CFDI cancellation notice failed', fn () => app(CFDICancellationNotifier::class)->requested($invoice->fresh()));

        // Las cancelaciones sin aceptacion pueden quedar listas en minutos:
        // se intenta una consulta inmediata; si falla, la resuelve el comando.
        try {
            return $this->checkCancellation($invoice->fresh());
        } catch (\Throwable $e) {
            Log::warning('Immediate CFDI cancellation check failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return $invoice->fresh();
        }
    }

    /**
     * Consulta al SAT el estatus de una cancelacion en proceso y la resuelve:
     * Estado "Cancelado" (con o sin aceptacion, o plazo vencido) cancela la
     * factura; "Solicitud rechazada" la regresa a vigente; cualquier otro
     * estatus solo se registra.
     *
     * @throws PacException
     */
    public function checkCancellation(CFDIInvoice $invoice): CFDIInvoice
    {
        if (!$invoice->isCancelPending()) {
            throw new PacException('El CFDI no tiene una cancelación en proceso');
        }

        $sat = $this->validateWithSAT($invoice);
        $estado = (string) ($sat['estado'] ?? '');
        $estatus = (string) ($sat['estatus_cancelacion'] ?? '');

        if (strcasecmp($estado, 'Cancelado') === 0) {
            return $this->resolveCancellation($invoice, 'cancelled', $estatus !== '' ? $estatus : 'Cancelado');
        }

        if (stripos($estatus, 'rechazad') !== false) {
            return $this->resolveCancellation($invoice, 'valid', $estatus);
        }

        $invoice->update([
            'cancellation_status' => $estatus !== '' ? $estatus : ($estado !== '' ? $estado : $invoice->cancellation_status),
            'cancellation_checked_at' => now(),
        ]);

        return $invoice->fresh();
    }

    /**
     * Cierra una cancelacion en proceso con su resultado final ('cancelled' o
     * 'valid' si el receptor la rechazo). Tambien lo usa el webhook del PAC.
     */
    public function resolveCancellation(CFDIInvoice $invoice, string $result, string $satStatus): CFDIInvoice
    {
        if (!$invoice->isCancelPending()) {
            return $invoice;
        }

        $cancelled = $result === 'cancelled';

        $invoice->update([
            'status' => $cancelled ? 'cancelled' : 'valid',
            'fecha_cancelacion' => $cancelled ? now() : null,
            'cancellation_status' => $satStatus,
            'cancellation_checked_at' => now(),
        ]);

        Log::info('CFDI cancellation resolved', [
            'invoice_id' => $invoice->id,
            'uuid' => $invoice->uuid,
            'result' => $result,
            'sat_status' => $satStatus,
        ]);

        $fresh = $invoice->fresh();

        if ($cancelled) {
            $this->afterCommit($fresh, 'CFDICancelled listener failed', fn () => event(new CFDICancelled($fresh)));
        }
        $this->afterCommit($fresh, 'CFDI cancellation notice failed', fn () => app(CFDICancellationNotifier::class)->resolved($fresh));

        return $fresh;
    }

    /** Guarda el error del PAC en la factura (solo error_message) y lo registra. */
    protected function recordFailure(CFDIInvoice $invoice, PacException $e, string $logMessage): void
    {
        $invoice->update(['error_message' => $e->getMessage()]);

        Log::error($logMessage, [
            'invoice_id' => $invoice->id,
            'error' => $e->getMessage(),
        ]);
    }

    /** Registra un error no previsto y lo envuelve en PacException, como antes. */
    protected function unexpected(CFDIInvoice $invoice, \Throwable $e, string $logMessage, string $prefix): PacException
    {
        Log::error($logMessage, [
            'invoice_id' => $invoice->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return new PacException($prefix . $e->getMessage());
    }

    /** Efecto posterior al commit: su fallo se registra y no se propaga. */
    protected function afterCommit(CFDIInvoice $invoice, string $logMessage, \Closure $effect): void
    {
        try {
            $effect();
        } catch (\Throwable $e) {
            Log::error($logMessage, [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate CFDI with SAT
     *
     * @param CFDIInvoice $invoice
     * @return array
     * @throws PacException
     */
    public function validateWithSAT(CFDIInvoice $invoice): array
    {
        if (!$invoice->isTimbrado()) {
            throw new PacException('El CFDI no está timbrado');
        }

        $companySetting = $invoice->companySetting;
        if (!$companySetting) {
            throw new PacException('No se encontró configuración de empresa');
        }

        return $this->pacService->validateWithSAT(
            uuid: $invoice->uuid,
            rfcEmisor: $companySetting->rfc,
            rfcReceptor: $invoice->receptor_rfc,
            total: $invoice->total / 100 // Convert from cents
        );
    }

    /**
     * Get cancellation status from PAC
     *
     * @param CFDIInvoice $invoice
     * @return array
     * @throws PacException
     */
    public function getCancellationStatus(CFDIInvoice $invoice): array
    {
        if (!$invoice->isTimbrado()) {
            throw new PacException('El CFDI no está timbrado');
        }

        $companySetting = $invoice->companySetting;
        if (!$companySetting) {
            throw new PacException('No se encontró configuración de empresa');
        }

        return $this->pacService->getCancellationStatus(
            uuid: $invoice->uuid,
            rfcEmisor: $companySetting->rfc
        );
    }

    /**
     * Validate invoice can be stamped
     *
     * @param CFDIInvoice $invoice
     * @throws PacException
     */
    protected function validateForStamping(CFDIInvoice $invoice): void
    {
        if (!$this->pacService->isEnabled()) {
            throw new PacException('El servicio de timbrado PAC no está habilitado');
        }

        if ($invoice->isTimbrado()) {
            throw new PacException('El CFDI ya está timbrado');
        }

        if ($invoice->isCancelled()) {
            throw new PacException('No se puede timbrar un CFDI cancelado');
        }

        if (empty($invoice->receptor_rfc)) {
            throw new PacException('Falta RFC del receptor');
        }

        if ($invoice->total <= 0) {
            throw new PacException('El total del CFDI debe ser mayor a 0');
        }

        // Validate has company settings
        if (!$invoice->companySetting) {
            throw new PacException('No se encontró configuración de empresa');
        }

        // Validate has items
        if ($invoice->items()->count() === 0) {
            throw new PacException('El CFDI no tiene conceptos');
        }
    }

    /**
     * Validate invoice can be cancelled
     *
     * @param CFDIInvoice $invoice
     * @throws PacException
     */
    protected function validateForCancellation(CFDIInvoice $invoice): void
    {
        if (!$this->pacService->isEnabled()) {
            throw new PacException('El servicio de timbrado PAC no está habilitado');
        }

        if (!$invoice->canBeCancelled()) {
            throw new PacException('El CFDI no puede ser cancelado');
        }

        if ($invoice->isCancelled()) {
            throw new PacException('El CFDI ya está cancelado');
        }
    }

    /**
     * Save stamped XML to storage
     *
     * @param CFDIInvoice $invoice
     * @param string $xml
     * @return string Path to saved file
     */
    protected function saveStampedXml(CFDIInvoice $invoice, string $xml): string
    {
        $xmlPath = config('billing.cfdi.xml_path', 'cfdi/xml');
        $filename = $this->generateXmlFilename($invoice);
        $fullPath = "{$xmlPath}/{$filename}";

        Storage::disk('local')->put($fullPath, $xml);

        $invoice->update(['xml_path' => $fullPath]);

        return $fullPath;
    }

    /**
     * Generate filename for XML
     *
     * @param CFDIInvoice $invoice
     * @return string
     */
    protected function generateXmlFilename(CFDIInvoice $invoice): string
    {
        $serie = $invoice->series ?? 'F';
        $folio = str_pad($invoice->folio, 6, '0', STR_PAD_LEFT);
        $uuid = $invoice->uuid ?? 'DRAFT';

        return "CFDI_{$serie}_{$folio}_{$uuid}.xml";
    }
}
