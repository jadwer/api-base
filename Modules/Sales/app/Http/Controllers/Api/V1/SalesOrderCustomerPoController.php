<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Sales\Models\SalesOrder;

/**
 * Fase A - Venta directa vs Pedido.
 *
 * Upload y descarga del PDF de la orden de compra del cliente (customer PO)
 * asociada a un pedido. Patron tomado de ProductUploadController, pero en
 * disco privado: el documento del cliente no debe ser accesible sin auth.
 */
class SalesOrderCustomerPoController extends Controller
{
    /**
     * Upload customer purchase order PDF.
     *
     * POST /api/v1/sales-orders/{salesOrder}/upload-customer-po
     *
     * Requires permission: sales-orders.update
     * Only PDF, max 10MB. Stored on the private disk at
     * sales-orders/{id}/customer-po/{uuid}.pdf
     */
    public function upload(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('sales-orders.update') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden - requires sales-orders.update permission'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB
        ]);

        $file = $request->file('file');
        $filename = Str::uuid() . '.pdf';

        // Replace previous file if one exists
        if ($salesOrder->customer_po_path && Storage::disk('private')->exists($salesOrder->customer_po_path)) {
            Storage::disk('private')->delete($salesOrder->customer_po_path);
        }

        $path = $file->storeAs("sales-orders/{$salesOrder->id}/customer-po", $filename, 'private');

        $salesOrder->update(['customer_po_path' => $path]);

        return response()->json([
            'path' => $path,
            'filename' => $filename,
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
            'message' => 'Orden de compra del cliente subida correctamente',
        ]);
    }

    /**
     * Download customer purchase order PDF.
     *
     * GET /api/v1/sales-orders/{salesOrder}/customer-po
     *
     * Requires permission: sales-orders.show
     */
    public function download(Request $request, SalesOrder $salesOrder)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('sales-orders.show') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden - requires sales-orders.show permission'], 403);
        }

        if (!$salesOrder->customer_po_path) {
            return response()->json([
                'error' => 'Esta orden no tiene orden de compra del cliente adjunta',
            ], 404);
        }

        if (!Storage::disk('private')->exists($salesOrder->customer_po_path)) {
            return response()->json([
                'error' => 'Archivo no encontrado',
            ], 404);
        }

        $downloadName = 'oc-cliente-' . ($salesOrder->customer_po_number ?: $salesOrder->order_number) . '.pdf';

        return Storage::disk('private')->download($salesOrder->customer_po_path, $downloadName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
