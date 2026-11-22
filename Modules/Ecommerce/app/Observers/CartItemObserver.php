<?php

namespace Modules\Ecommerce\Observers;

use App\Services\CurrencyConversionService;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Product\Models\Product;

class CartItemObserver
{
    /**
     * Handle the CartItem "creating" event.
     *
     * When a cart item is being created, check if the product's currency
     * differs from the cart's currency. If so, convert the price and store
     * the original currency info for traceability.
     */
    public function creating(CartItem $cartItem): void
    {
        $cart = $cartItem->shoppingCart ?? ShoppingCart::find($cartItem->shopping_cart_id);
        $product = $cartItem->product ?? Product::find($cartItem->product_id);

        // Por la API la partida llega sin precio (los montos son de solo lectura
        // en el Schema): se toma del catalogo. Un producto sujeto a cotizacion
        // entra en 0 y el checkout lo rechaza (QuoteOnlyCartGuard). El codigo
        // interno que ya trae precio (cotizaciones, pruebas) no se toca.
        if ($product && ($cartItem->getAttributes()['unit_price'] ?? null) === null) {
            $price = $product->requiresQuote() ? 0.0 : round((float) $product->getAttribute('price'), 2);
            $cartItem->setAttribute('unit_price', $price);
            $cartItem->setAttribute('original_price', $price);
            $cartItem->setAttribute('discount_percent', 0);
            $cartItem->setAttribute('discount_amount', 0);
            $cartItem->setAttribute('tax_rate', (float) $product->getAttribute('effective_tax_rate'));
            $this->recalculate($cartItem);
        }

        if (!$cart || !$product || !$product->currency) {
            return;
        }

        $cartCurrency = $cart->currency ?? 'MXN';
        $productCurrency = $product->currency->code ?? 'MXN';

        // If currencies differ, convert the price
        if ($productCurrency !== $cartCurrency) {
            $service = app(CurrencyConversionService::class);
            $result = $service->convert($cartItem->unit_price, $productCurrency, $cartCurrency);

            // Store original price info for traceability
            $cartItem->original_currency_code = $productCurrency;
            $cartItem->original_unit_price = $cartItem->unit_price;
            $cartItem->exchange_rate_used = $result->effectiveRate;

            // El descuento en monto viene en la moneda del producto: se convierte
            // con la misma tasa (antes quedaba sin convertir y podia superar el
            // importe ya convertido; lo destapo el tope de descuento, 22-10).
            if ((float) $cartItem->discount_amount > 0 && (float) $cartItem->unit_price > 0) {
                $cartItem->discount_amount = round((float) $cartItem->discount_amount * $result->convertedAmount / (float) $cartItem->unit_price, 2);
            }

            // Update price to converted amount
            $cartItem->unit_price = $result->convertedAmount;
            $cartItem->original_price = $result->convertedAmount;

            // Recalculate subtotal, tax, total
            $quantity = $cartItem->quantity ?? 1;
            $cartItem->subtotal = round($cartItem->unit_price * $quantity, 2);
            $taxRate = $cartItem->tax_rate ?? 16;
            $cartItem->tax_amount = round($cartItem->subtotal * ($taxRate / 100), 2);
            $cartItem->total = round($cartItem->subtotal + $cartItem->tax_amount, 2);
        }
    }

    /**
     * Al cambiar la cantidad se recalculan los importes con el precio ya
     * guardado: el cliente manda solo la cantidad.
     */
    public function updating(CartItem $cartItem): void
    {
        if ($cartItem->isDirty('quantity') && !$cartItem->isDirty(['subtotal', 'tax_amount', 'total'])) {
            $this->recalculate($cartItem);
        }
    }

    private function recalculate(CartItem $cartItem): void
    {
        $quantity = (float) ($cartItem->getAttribute('quantity') ?? 1);
        // Misma convencion que la conversion de moneda de arriba: subtotal bruto.
        $subtotal = round((float) $cartItem->getAttribute('unit_price') * $quantity, 2);
        $tax = round($subtotal * ((float) ($cartItem->getAttribute('tax_rate') ?? 0) / 100), 2);
        $cartItem->setAttribute('subtotal', $subtotal);
        $cartItem->setAttribute('tax_amount', $tax);
        $cartItem->setAttribute('total', round($subtotal + $tax, 2));
    }
}
