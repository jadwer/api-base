<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Billing\JsonApi\V1\CfdiInvoices\CFDIInvoiceSchema;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\CFDI\CFDIXMLGenerator;
use Modules\Billing\Services\CFDI\CFDIPDFGenerator;
use Modules\Billing\Services\CFDI\CFDIStampingService;
use Modules\Billing\Services\CFDI\PrefacturaPDFGenerator;
use Modules\Sales\Models\SalesOrder;
use Modules\Billing\Exceptions\PacException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CFDIInvoiceController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;

    /**
     * Generate CFDI XML for an invoice
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param CFDIXMLGenerator $xmlGenerator
     * @return JsonResponse
     */
    public function generateXml(CFDIInvoice $cfdiInvoice, CFDIXMLGenerator $xmlGenerator): JsonResponse
    {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.generate-xml')) {
            abort(403, 'No tiene permisos para generar XML CFDI');
        }

        try {
            $xml = $xmlGenerator->generate($cfdiInvoice);

            // Update invoice with XML
            $cfdiInvoice->update([
                'xml_original' => $xml,
            ]);

            return response()->json([
                'message' => 'XML CFDI generado correctamente',
                'xml' => $xml,
                'invoice_id' => $cfdiInvoice->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar XML CFDI',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate CFDI PDF for an invoice
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param CFDIPDFGenerator $pdfGenerator
     * @return JsonResponse
     */
    public function generatePdf(CFDIInvoice $cfdiInvoice, CFDIPDFGenerator $pdfGenerator): JsonResponse
    {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.generate-pdf')) {
            abort(403, 'No tiene permisos para generar PDF CFDI');
        }

        try {
            $pdfPath = $pdfGenerator->generate($cfdiInvoice);

            return response()->json([
                'message' => 'PDF CFDI generado correctamente',
                'pdf_path' => $cfdiInvoice->pdf_path,
                'pdf_url' => asset('storage/' . $cfdiInvoice->pdf_path),
                'invoice_id' => $cfdiInvoice->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar PDF CFDI',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download CFDI PDF
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param CFDIPDFGenerator $pdfGenerator
     * @return StreamedResponse
     */
    public function downloadPdf(CFDIInvoice $cfdiInvoice, CFDIPDFGenerator $pdfGenerator): StreamedResponse
    {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.download-pdf')) {
            abort(403, 'No tiene permisos para descargar PDF CFDI');
        }

        try {
            return $pdfGenerator->download($cfdiInvoice);
        } catch (\Exception $e) {
            abort(500, 'Error al descargar PDF CFDI: ' . $e->getMessage());
        }
    }

    /**
     * Preview CFDI PDF (stream inline)
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param CFDIPDFGenerator $pdfGenerator
     * @return BinaryFileResponse
     */
    public function previewPdf(CFDIInvoice $cfdiInvoice, CFDIPDFGenerator $pdfGenerator): BinaryFileResponse
    {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.preview-pdf')) {
            abort(403, 'No tiene permisos para previsualizar PDF CFDI');
        }

        try {
            return $pdfGenerator->preview($cfdiInvoice);
        } catch (\Exception $e) {
            abort(500, 'Error al previsualizar PDF CFDI: ' . $e->getMessage());
        }
    }

    /**
     * Download CFDI XML
     *
     * @param CFDIInvoice $cfdiInvoice
     * @return Response
     */
    public function downloadXml(CFDIInvoice $cfdiInvoice): Response
    {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.download-xml')) {
            abort(403, 'No tiene permisos para descargar XML CFDI');
        }

        if (!$cfdiInvoice->xml_timbrado && !$cfdiInvoice->xml_original) {
            abort(404, 'No hay XML disponible para esta factura');
        }

        // Prefer stamped XML over original
        $xml = $cfdiInvoice->xml_timbrado ?? $cfdiInvoice->xml_original;
        $filename = $this->generateXmlFilename($cfdiInvoice);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate filename for XML download
     *
     * @param CFDIInvoice $invoice
     * @return string
     */
    protected function generateXmlFilename(CFDIInvoice $invoice): string
    {
        $serie = $invoice->series ?? 'F';
        $folio = str_pad($invoice->folio, 6, '0', STR_PAD_LEFT);

        if ($invoice->uuid) {
            return "CFDI_{$serie}_{$folio}_{$invoice->uuid}.xml";
        }

        return "CFDI_{$serie}_{$folio}_DRAFT.xml";
    }

    /**
     * Stamp CFDI with PAC
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param Request $request
     * @param CFDIStampingService $stampingService
     * @return JsonResponse
     */
    public function stamp(
        CFDIInvoice $cfdiInvoice,
        Request $request,
        CFDIStampingService $stampingService
    ): JsonResponse {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.stamp')) {
            abort(403, 'No tiene permisos para timbrar CFDI');
        }

        try {
            $regenerateXml = $request->boolean('regenerate_xml', false);

            $invoice = $stampingService->stamp($cfdiInvoice, $regenerateXml);

            return response()->json([
                'message' => 'CFDI timbrado correctamente',
                'data' => [
                    'id' => $invoice->id,
                    'uuid' => $invoice->uuid,
                    'fecha_timbrado' => $invoice->fecha_timbrado,
                    'status' => $invoice->status,
                    'folio_completo' => $invoice->getFolioCompleto(),
                ],
            ]);
        } catch (PacException $e) {
            return response()->json([
                'message' => 'Error al timbrar CFDI',
                'error' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error inesperado al timbrar CFDI',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel CFDI with PAC
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param Request $request
     * @param CFDIStampingService $stampingService
     * @return JsonResponse
     */
    public function cancel(
        CFDIInvoice $cfdiInvoice,
        Request $request,
        CFDIStampingService $stampingService
    ): JsonResponse {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.cancel')) {
            abort(403, 'No tiene permisos para cancelar CFDI');
        }

        // Validate request
        $validated = $request->validate([
            'motivo_cancelacion' => ['required', 'string', 'in:01,02,03,04'],
            'uuid_sustitucion' => ['nullable', 'string', 'uuid'],
        ]);

        try {
            $motivoCancelacion = $validated['motivo_cancelacion'];
            $invoiceSustitucion = null;

            // If motive is 01, find replacement invoice by UUID
            if ($motivoCancelacion === '01') {
                if (empty($validated['uuid_sustitucion'])) {
                    return response()->json([
                        'message' => 'El motivo 01 requiere UUID de sustitución',
                    ], 422);
                }

                $invoiceSustitucion = CFDIInvoice::where('uuid', $validated['uuid_sustitucion'])->first();

                if (!$invoiceSustitucion) {
                    return response()->json([
                        'message' => 'No se encontró el CFDI de sustitución',
                    ], 404);
                }
            }

            $invoice = $stampingService->cancel(
                $cfdiInvoice,
                $motivoCancelacion,
                $invoiceSustitucion
            );

            return response()->json([
                'message' => 'CFDI cancelado correctamente',
                'data' => [
                    'id' => $invoice->id,
                    'uuid' => $invoice->uuid,
                    'fecha_cancelacion' => $invoice->fecha_cancelacion,
                    'status' => $invoice->status,
                    'motivo' => $motivoCancelacion,
                ],
            ]);
        } catch (PacException $e) {
            return response()->json([
                'message' => 'Error al cancelar CFDI',
                'error' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error inesperado al cancelar CFDI',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate CFDI with SAT
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param CFDIStampingService $stampingService
     * @return JsonResponse
     */
    public function validateSAT(
        CFDIInvoice $cfdiInvoice,
        CFDIStampingService $stampingService
    ): JsonResponse {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.validate')) {
            abort(403, 'No tiene permisos para validar CFDI');
        }

        try {
            $validationData = $stampingService->validateWithSAT($cfdiInvoice);

            return response()->json([
                'message' => 'Validación completada',
                'data' => $validationData,
            ]);
        } catch (PacException $e) {
            return response()->json([
                'message' => 'Error al validar CFDI',
                'error' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error inesperado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cancellation status from PAC
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param CFDIStampingService $stampingService
     * @return JsonResponse
     */
    public function cancellationStatus(
        CFDIInvoice $cfdiInvoice,
        CFDIStampingService $stampingService
    ): JsonResponse {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.cancellation-status')) {
            abort(403, 'No tiene permisos para consultar estatus de cancelación');
        }

        try {
            $statusData = $stampingService->getCancellationStatus($cfdiInvoice);

            return response()->json([
                'message' => 'Estatus de cancelación obtenido',
                'data' => $statusData,
            ]);
        } catch (PacException $e) {
            return response()->json([
                'message' => 'Error al consultar estatus',
                'error' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error inesperado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send CFDI by email
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param Request $request
     * @param CFDIPDFGenerator $pdfGenerator
     * @return JsonResponse
     */
    public function sendEmail(
        CFDIInvoice $cfdiInvoice,
        Request $request,
        CFDIPDFGenerator $pdfGenerator
    ): JsonResponse {
        // Check permission
        if (Gate::denies('billing.cfdi-invoices.send-email')) {
            abort(403, 'No tiene permisos para enviar CFDI por correo');
        }

        // Validate request
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'include_xml' => ['nullable', 'boolean'],
        ]);

        try {
            // Ensure PDF exists
            if (!$cfdiInvoice->pdf_path || !Storage::disk('public')->exists($cfdiInvoice->pdf_path)) {
                $pdfGenerator->generate($cfdiInvoice);
                $cfdiInvoice->refresh();
            }

            $email = $validated['email'];
            $subject = $validated['subject'] ?? 'CFDI ' . $cfdiInvoice->getFolioCompleto();
            $body = $validated['message'] ?? $this->getDefaultEmailBody($cfdiInvoice);
            $includeXml = $validated['include_xml'] ?? true;

            // Prepare attachments
            $attachments = [];

            // PDF attachment
            if ($cfdiInvoice->pdf_path && Storage::disk('public')->exists($cfdiInvoice->pdf_path)) {
                $attachments[] = [
                    'path' => Storage::disk('public')->path($cfdiInvoice->pdf_path),
                    'name' => $this->generatePdfFilename($cfdiInvoice),
                    'mime' => 'application/pdf',
                ];
            }

            // XML attachment (if requested and available)
            if ($includeXml && ($cfdiInvoice->xml_timbrado || $cfdiInvoice->xml_original)) {
                $xml = $cfdiInvoice->xml_timbrado ?? $cfdiInvoice->xml_original;
                $attachments[] = [
                    'data' => $xml,
                    'name' => $this->generateXmlFilename($cfdiInvoice),
                    'mime' => 'application/xml',
                ];
            }

            // Send email
            Mail::send([], [], function ($mail) use ($email, $subject, $body, $attachments, $cfdiInvoice) {
                $mail->to($email)
                    ->subject($subject)
                    ->html($this->wrapEmailBody($body, $cfdiInvoice));

                foreach ($attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        $mail->attach($attachment['path'], [
                            'as' => $attachment['name'],
                            'mime' => $attachment['mime'],
                        ]);
                    } elseif (isset($attachment['data'])) {
                        $mail->attachData($attachment['data'], $attachment['name'], [
                            'mime' => $attachment['mime'],
                        ]);
                    }
                }
            });

            return response()->json([
                'message' => 'CFDI enviado correctamente por correo',
                'data' => [
                    'id' => $cfdiInvoice->id,
                    'email' => $email,
                    'folio' => $cfdiInvoice->getFolioCompleto(),
                    'attachments' => count($attachments),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al enviar CFDI por correo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate default email body for CFDI
     *
     * @param CFDIInvoice $invoice
     * @return string
     */
    protected function getDefaultEmailBody(CFDIInvoice $invoice): string
    {
        $folio = $invoice->getFolioCompleto();
        $total = number_format($invoice->total / 100, 2);

        return "Estimado cliente,\n\n" .
            "Adjunto encontrará su Comprobante Fiscal Digital por Internet (CFDI) con los siguientes datos:\n\n" .
            "Folio: {$folio}\n" .
            "UUID: " . ($invoice->uuid ?? 'Pendiente de timbrado') . "\n" .
            "Total: \${$total} {$invoice->moneda}\n\n" .
            "Gracias por su preferencia.";
    }

    /**
     * Wrap email body in HTML
     *
     * @param string $body
     * @param CFDIInvoice $invoice
     * @return string
     */
    protected function wrapEmailBody(string $body, CFDIInvoice $invoice): string
    {
        $companyName = $invoice->companySetting?->company_name ?? config('app.name');

        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #0066cc; padding-bottom: 10px; margin-bottom: 20px; }
        .content { white-space: pre-wrap; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>{$companyName}</h2>
        </div>
        <div class='content'>{$body}</div>
        <div class='footer'>
            <p>Este correo fue generado automáticamente. Por favor no responda a este mensaje.</p>
        </div>
    </div>
</body>
</html>";
    }

    /**
     * Generate filename for PDF
     *
     * @param CFDIInvoice $invoice
     * @return string
     */
    protected function generatePdfFilename(CFDIInvoice $invoice): string
    {
        $serie = $invoice->series ?? 'F';
        $folio = str_pad($invoice->folio, 6, '0', STR_PAD_LEFT);

        if ($invoice->uuid) {
            return "CFDI_{$serie}_{$folio}_{$invoice->uuid}.pdf";
        }

        return "CFDI_{$serie}_{$folio}.pdf";
    }

    /**
     * Generate Prefactura PDF from a draft CFDIInvoice
     *
     * Prefactura is a preview PDF with "SIN VALIDEZ FISCAL" watermark
     * that allows reviewing the invoice before stamping with PAC.
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param PrefacturaPDFGenerator $generator
     * @return JsonResponse
     */
    public function prefactura(
        CFDIInvoice $cfdiInvoice,
        PrefacturaPDFGenerator $generator
    ): JsonResponse {
        if (Gate::denies('billing.cfdi-invoices.prefactura')) {
            abort(403, 'No tiene permisos para generar prefactura');
        }

        try {
            $path = $generator->generate($cfdiInvoice);

            return response()->json([
                'message' => 'Prefactura generada correctamente',
                'data' => [
                    'id' => $cfdiInvoice->id,
                    'folio' => $cfdiInvoice->getFolioCompleto(),
                    'pdf_path' => $path,
                    'is_stamped' => (bool) $cfdiInvoice->uuid,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar prefactura',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download Prefactura PDF
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param PrefacturaPDFGenerator $generator
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadPrefactura(
        CFDIInvoice $cfdiInvoice,
        PrefacturaPDFGenerator $generator
    ) {
        if (Gate::denies('billing.cfdi-invoices.prefactura')) {
            abort(403, 'No tiene permisos para descargar prefactura');
        }

        try {
            return $generator->download($cfdiInvoice);
        } catch (\Exception $e) {
            abort(500, 'Error al descargar prefactura: ' . $e->getMessage());
        }
    }

    /**
     * Preview Prefactura PDF inline
     *
     * @param CFDIInvoice $cfdiInvoice
     * @param PrefacturaPDFGenerator $generator
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function previewPrefactura(
        CFDIInvoice $cfdiInvoice,
        PrefacturaPDFGenerator $generator
    ) {
        if (Gate::denies('billing.cfdi-invoices.prefactura')) {
            abort(403, 'No tiene permisos para previsualizar prefactura');
        }

        try {
            return $generator->preview($cfdiInvoice);
        } catch (\Exception $e) {
            abort(500, 'Error al previsualizar prefactura: ' . $e->getMessage());
        }
    }

    /**
     * Generate Prefactura from a SalesOrder without creating a CFDIInvoice
     *
     * This allows previewing how an invoice would look before creating
     * the actual CFDI record. Useful for customer review before billing.
     *
     * @param SalesOrder $salesOrder
     * @param Request $request
     * @param PrefacturaPDFGenerator $generator
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function prefacturaFromOrder(
        SalesOrder $salesOrder,
        Request $request,
        PrefacturaPDFGenerator $generator
    ) {
        if (Gate::denies('billing.cfdi-invoices.prefactura')) {
            abort(403, 'No tiene permisos para generar prefactura');
        }

        // Optional CFDI data overrides from request
        $cfdiData = $request->validate([
            'receptor_rfc' => ['nullable', 'string', 'max:13'],
            'receptor_uso_cfdi' => ['nullable', 'string', 'max:10'],
            'receptor_regimen_fiscal' => ['nullable', 'string', 'max:3'],
            'receptor_domicilio_fiscal' => ['nullable', 'string', 'max:5'],
            'metodo_pago' => ['nullable', 'string', 'in:PUE,PPD'],
            'forma_pago' => ['nullable', 'string', 'max:2'],
            'series' => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $download = $request->boolean('download', false);

            if ($download) {
                return $generator->downloadFromOrder($salesOrder, $cfdiData);
            }

            return $generator->streamFromOrder($salesOrder, $cfdiData);
        } catch (\Exception $e) {
            abort(500, 'Error al generar prefactura desde orden: ' . $e->getMessage());
        }
    }
}
