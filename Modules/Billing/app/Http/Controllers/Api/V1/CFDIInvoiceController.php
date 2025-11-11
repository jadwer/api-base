<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Billing\JsonApi\V1\CfdiInvoices\CFDIInvoiceSchema;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\CFDI\CFDIXMLGenerator;
use Modules\Billing\Services\CFDI\CFDIPDFGenerator;
use Modules\Billing\Services\CFDI\CFDIStampingService;
use Modules\Billing\Exceptions\PacException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Gate;

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
     * @return StreamedResponse
     */
    public function previewPdf(CFDIInvoice $cfdiInvoice, CFDIPDFGenerator $pdfGenerator): StreamedResponse
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
}
