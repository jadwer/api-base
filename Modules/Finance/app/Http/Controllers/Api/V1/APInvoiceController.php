<?php

namespace Modules\Finance\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Services\APInvoiceService;

class APInvoiceController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    /**
     * POST ap-invoices/{apInvoice}/supplier-invoice: captura la factura del
     * proveedor (folio o UUID e IVA) sobre la CxP nacida de la recepcion.
     */
    public function supplierInvoice(Request $request, APInvoice $apInvoice, APInvoiceService $service): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }
        if (!$user->can('ap-invoices.update') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'No tiene permisos para esta acción.'], 403);
        }

        $validated = $request->validate([
            'supplier_invoice_number' => ['required', 'string', 'max:60'],
            'supplier_uuid' => ['nullable', 'uuid'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
        ], [
            'supplier_invoice_number.required' => 'Indica el folio de la factura del proveedor.',
            'supplier_uuid.uuid' => 'El UUID no tiene el formato del SAT (8-4-4-4-12).',
            'subtotal.required' => 'Indica el subtotal de la factura del proveedor.',
            'tax_amount.required' => 'Indica el IVA de la factura del proveedor (0 si no lleva).',
        ], [
            'supplier_invoice_number' => 'folio de la factura del proveedor',
            'supplier_uuid' => 'UUID',
            'subtotal' => 'subtotal',
            'tax_amount' => 'IVA',
        ]);

        try {
            $invoice = $service->registerSupplierInvoice($apInvoice, $validated, $user->id);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            // Periodo fiscal cerrado o cuentas faltantes: la transaccion ya hizo rollback.
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Factura del proveedor capturada',
            'invoice' => [
                'id' => $invoice->id,
                'subtotal' => round((float) $invoice->subtotal, 2),
                'tax_amount' => round((float) $invoice->tax_amount, 2),
                'total_amount' => round((float) $invoice->total_amount, 2),
            ],
        ]);
    }
}
