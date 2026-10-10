<?php

namespace Modules\Ecommerce\Support;

use Illuminate\Http\JsonResponse;

/**
 * Carrito con productos de precio sujeto a cotizacion que intento entrar al
 * flujo de pago desde un servicio (CheckoutService). Se renderiza como el 422
 * de QuoteOnlyCartGuard.
 */
class QuoteOnlyCartException extends \DomainException
{
    /**
     * @param list<array{product_id: int, product_name: string|null, product_sku: string|null}> $items
     */
    public function __construct(public readonly array $items)
    {
        parent::__construct(QuoteOnlyCartGuard::MESSAGE . ' Productos: ' . QuoteOnlyCartGuard::names($items) . '.');
    }

    public function render(): JsonResponse
    {
        return QuoteOnlyCartGuard::responseFor($this->items);
    }
}
