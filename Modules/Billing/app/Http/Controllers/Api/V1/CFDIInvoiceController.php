<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Billing\JsonApi\V1\CfdiInvoices\CFDIInvoiceSchema;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\CFDI\CFDIXMLGenerator;
use Modules\Billing\Services\CFDI\CFDIPDFGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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
     * @return Response
     */
    public function downloadPdf(CFDIInvoice $cfdiInvoice, CFDIPDFGenerator $pdfGenerator): Response
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
     * @return Response
     */
    public function previewPdf(CFDIInvoice $cfdiInvoice, CFDIPDFGenerator $pdfGenerator): Response
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
}
