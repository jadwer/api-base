<?php

namespace Modules\Audit\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Audit\Models\Audit as Activity;

/**
 * Tipos de registro que la bitacora tiene de verdad, con su etiqueta. La
 * pantalla tenia una lista escrita a mano con clases que no existen (regla 7:
 * lo que el sistema ofrece en un filtro lo sirve el backend). PLA-19.
 */
class AuditTypeController extends Controller
{
    /** Etiqueta por nombre corto de la clase; lo que no este aqui sale con el nombre separado en palabras. */
    private const LABELS = [
        'Product' => 'Producto', 'Category' => 'Categoría', 'Brand' => 'Marca', 'Unit' => 'Unidad',
        'User' => 'Usuario', 'Contact' => 'Contacto', 'ContactAddress' => 'Dirección de contacto',
        'ContactPerson' => 'Persona de contacto', 'ContactDocument' => 'Documento de contacto',
        'Quote' => 'Cotización', 'QuoteItem' => 'Partida de cotización',
        'SalesOrder' => 'Pedido', 'SalesOrderItem' => 'Partida de pedido', 'Remission' => 'Remisión',
        'PurchaseOrder' => 'Orden de compra', 'PurchaseOrderItem' => 'Partida de orden de compra',
        'Warehouse' => 'Almacén', 'WarehouseLocation' => 'Ubicación de almacén', 'Stock' => 'Existencia',
        'InventoryMovement' => 'Movimiento de inventario', 'ProductBatch' => 'Lote',
        'CFDIInvoice' => 'Factura CFDI', 'PaymentTransaction' => 'Transacción de pago',
        'CompanySetting' => 'Configuración fiscal', 'ARInvoice' => 'Cuenta por cobrar',
        'APInvoice' => 'Cuenta por pagar', 'Payment' => 'Cobro', 'APPayment' => 'Pago a proveedor',
        'BankAccount' => 'Cuenta bancaria', 'Account' => 'Cuenta contable', 'JournalEntry' => 'Póliza',
        'Employee' => 'Empleado', 'Department' => 'Departamento', 'Campaign' => 'Campaña',
        'Lead' => 'Prospecto', 'Opportunity' => 'Oportunidad', 'Page' => 'Página',
        'EmailTemplate' => 'Plantilla de correo', 'AppSetting' => 'Ajuste', 'Role' => 'Rol',
        'Branch' => 'Sucursal', 'Coupon' => 'Cupón', 'ShoppingCart' => 'Carrito',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (! $user || ! $user->can('audit.index')) {
            return response()->json(['errors' => [['status' => '403', 'detail' => 'No tiene permisos para consultar la bitácora.']]], 403);
        }

        $types = Activity::query()->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type')
            ->map(fn (string $type) => ['value' => $type, 'label' => self::label($type)])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['data' => $types]);
    }

    public static function label(string $type): string
    {
        $short = class_basename($type);

        return self::LABELS[$short] ?? Str::ucfirst(Str::lower(Str::headline($short)));
    }
}
