<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use App\Exports\QueryExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Purchase\Models\PurchaseOrder;
use Symfony\Component\HttpFoundation\Response;

/** Exporta el listado de ordenes de compra a Excel con los filtros del listado (COM-16). */
class PurchaseOrderExportController extends Controller
{
    private const STATUS = [
        'draft' => 'Borrador', 'pending' => 'Pendiente', 'approved' => 'Aprobada', 'partially_received' => 'Recibida parcial',
        'received' => 'Recibida', 'completed' => 'Completada', 'cancelled' => 'Cancelada', 'rejected' => 'Rechazada',
    ];

    public function __invoke(Request $request): Response
    {
        $user = $request->user('sanctum');
        if (! $user || ! $user->can('purchase-orders.index')) {
            return response()->json(['errors' => [['status' => '403', 'detail' => 'No tiene permisos para exportar órdenes de compra.']]], 403);
        }

        $filter = (array) $request->query('filter', []);
        $query = PurchaseOrder::query()->with(['contact:id,name', 'warehouse:id,name'])->orderByDesc('order_date')->orderByDesc('id');

        if (trim((string) ($filter['search'] ?? '')) !== '') {
            $query->search((string) $filter['search']);
        }
        foreach (['status' => 'status', 'contact' => 'contact_id', 'warehouse' => 'warehouse_id', 'branch' => 'branch_id'] as $key => $column) {
            if (($filter[$key] ?? '') !== '') {
                $query->where($column, $filter[$key]);
            }
        }

        if (filter_var($filter['pending_receipt'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->pendingReceipt();
        }

        $export = new QueryExport(
            $query,
            ['Folio', 'Fecha', 'Proveedor', 'Almacén', 'Estatus', 'Total', 'Llegada estimada'],
            function (PurchaseOrder $o): array {
                $date = $o->getAttribute('order_date');
                $eta = $o->getAttribute('expected_arrival_date');
                $status = (string) $o->getAttribute('status');

                return [
                    $o->getAttribute('order_number'),
                    $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : null,
                    $o->contact?->getAttribute('name'),
                    $o->warehouse?->getAttribute('name'),
                    self::STATUS[$status] ?? $status,
                    (float) $o->getAttribute('total_amount'),
                    $eta instanceof \DateTimeInterface ? $eta->format('Y-m-d') : null,
                ];
            },
            'Órdenes de compra'
        );

        return Excel::download($export, 'ordenes-de-compra-'.now()->format('Y-m-d').'.xlsx');
    }
}
