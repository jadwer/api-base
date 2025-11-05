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

        DB::beginTransaction();

        try {
            // Generate or use existing XML
            $xml = $regenerateXml || empty($invoice->xml_original)
                ? $this->xmlGenerator->generate($invoice)
                : $invoice->xml_original;

            // Save original XML if regenerated
            if ($regenerateXml || empty($invoice->xml_original)) {
                $invoice->update(['xml_original' => $xml]);
            }

            // Call PAC to stamp
            $stampData = $this->pacService->stamp($xml);

            // Update invoice with stamping data
            $invoice->update([
                'uuid' => $stampData['uuid'],
                'fecha_timbrado' => $stampData['fecha_timbrado'],
                'xml_timbrado' => $stampData['xml_timbrado'],
                'qr_code' => $stampData['qr_code'] ?? null,
                'pac_response' => $stampData['pac_response'],
                'status' => 'valid',
            ]);

            // Save stamped XML to storage
            $this->saveStampedXml($invoice, $stampData['xml_timbrado']);

            // Generate PDF with stamped data
            try {
                $this->pdfGenerator->generate($invoice->fresh());
            } catch (\Exception $e) {
                Log::warning('PDF generation failed after stamping', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the stamping if PDF generation fails
            }

            DB::commit();

            // Dispatch event
            event(new CFDIStamped($invoice->fresh()));

            Log::info('CFDI stamped successfully', [
                'invoice_id' => $invoice->id,
                'uuid' => $stampData['uuid'],
            ]);

            return $invoice->fresh();
        } catch (PacException $e) {
            DB::rollBack();

            // Save error message
            $invoice->update([
                'error_message' => $e->getMessage(),
            ]);

            Log::error('CFDI stamping failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Unexpected error during CFDI stamping', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new PacException('Error inesperado al timbrar CFDI: ' . $e->getMessage());
        }
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

        DB::beginTransaction();

        try {
            // Get company settings for RFC
            $companySetting = $invoice->companySetting;
            if (!$companySetting) {
                throw new PacException('No se encontró configuración de empresa');
            }

            $uuidSustitucion = null;
            if ($invoiceSustitucion) {
                if (!$invoiceSustitucion->uuid) {
                    throw new PacException('El CFDI de sustitución no está timbrado');
                }
                $uuidSustitucion = $invoiceSustitucion->uuid;
            }

            // Call PAC to cancel
            $cancelData = $this->pacService->cancel(
                uuid: $invoice->uuid,
                rfcEmisor: $companySetting->rfc,
                rfcReceptor: $invoice->receptor_rfc,
                total: $invoice->total / 100, // Convert from cents
                motivoCancelacion: $motivoCancelacion,
                uuidSustitucion: $uuidSustitucion
            );

            // Update invoice
            $invoice->update([
                'status' => 'cancelled',
                'fecha_cancelacion' => $cancelData['fecha_cancelacion'],
                'cfdi_relacionado_tipo' => $motivoCancelacion,
                'cfdi_relacionado_uuids' => $uuidSustitucion ? [$uuidSustitucion] : null,
                'pac_response' => array_merge(
                    $invoice->pac_response ?? [],
                    ['cancellation' => $cancelData['pac_response']]
                ),
            ]);

            DB::commit();

            // Dispatch event
            event(new CFDICancelled($invoice->fresh()));

            Log::info('CFDI cancelled successfully', [
                'invoice_id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'motive' => $motivoCancelacion,
            ]);

            return $invoice->fresh();
        } catch (PacException $e) {
            DB::rollBack();

            // Save error message
            $invoice->update([
                'error_message' => $e->getMessage(),
            ]);

            Log::error('CFDI cancellation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Unexpected error during CFDI cancellation', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            throw new PacException('Error inesperado al cancelar CFDI: ' . $e->getMessage());
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
