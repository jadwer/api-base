<?php

namespace Modules\Sales\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * P2b (D8): una linea de pedido o venta nunca se guarda con precio unitario
 * <= 0 (un precio cobrado o mostrado en $0 obliga a respetarlo ante PROFECO).
 *
 * La lanza SalesOrderItem al guardar, asi cubre cualquier camino (JSON:API,
 * convert de cotizacion, checkout, servicios y consola) aunque el camino ya
 * tenga su propia validacion. Se renderiza como 422 con el formato de errores
 * JSON:API.
 */
class SaleLineWithoutPriceException extends \DomainException
{
    public function __construct(
        public readonly ?int $productId = null,
        public readonly ?string $productName = null,
    ) {
        parent::__construct(
            'El precio unitario de la línea'
            . ($productName ? ' de ' . $productName : '')
            . ' debe ser mayor a $0. Un producto sin precio de venta solo se puede cotizar.'
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [[
                'status' => '422',
                'code' => 'sale_line_without_price',
                'title' => 'Precio de venta requerido',
                'detail' => $this->getMessage(),
                'source' => ['pointer' => '/data/attributes/unitPrice'],
                'meta' => array_filter([
                    'product_id' => $this->productId,
                    'product_name' => $this->productName,
                ], fn ($value) => $value !== null),
            ]],
        ], 422);
    }
}
