<?php

namespace Modules\Ecommerce\Support;

use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Product\Models\Product;

/**
 * P2 granel: un producto con precio sujeto a cotizacion no se compra directo.
 * El carrito si lo admite (sirve para armar la cotizacion: quotes/from-cart),
 * pero toda entrada del flujo de pago lo rechaza con la lista de productos.
 *
 * Entradas cubiertas: ShoppingCartController::checkout (crea la orden antes
 * de cobrar), CheckoutService::initiateCheckout y el alta de checkout-sessions
 * (CheckoutSessionRequest). La creacion del PaymentIntent solo recibe un monto
 * y la orden ya existe, asi que no necesita su propio candado.
 */
final class QuoteOnlyCartGuard
{
    public const MESSAGE = 'Tu carrito tiene productos con precio sujeto a cotizacion; no se pueden comprar en linea. Solicita una cotizacion para ellos o quitalos del carrito para continuar con el pago.';

    /**
     * @return list<array{product_id: int, product_name: string|null, product_sku: string|null}>
     */
    public static function quoteOnlyItems(ShoppingCart $cart): array
    {
        $cart->loadMissing('cartItems.product');

        $items = [];
        foreach ($cart->cartItems as $cartItem) {
            /** @var Product|null $product */
            $product = $cartItem->product;
            if ($product && $product->isQuoteOnly()) {
                $items[] = [
                    'product_id' => (int) $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                ];
            }
        }

        return $items;
    }

    /**
     * Respuesta 422 con detalle por producto, o null si el carrito se puede pagar.
     */
    public static function rejection(ShoppingCart $cart): ?JsonResponse
    {
        $items = self::quoteOnlyItems($cart);

        return $items === [] ? null : self::responseFor($items);
    }

    /**
     * @param list<array{product_id: int, product_name: string|null, product_sku: string|null}> $items
     */
    public static function responseFor(array $items): JsonResponse
    {
        return response()->json([
            'error' => self::MESSAGE,
            'message' => self::MESSAGE . ' Productos: ' . self::names($items) . '.',
            'quote_only_items' => $items,
            // El FE ofrece el camino de cotizacion (igual que con stock insuficiente).
            'quote_cta' => true,
        ], 422);
    }

    /**
     * @param list<array{product_id: int, product_name: string|null, product_sku: string|null}> $items
     */
    public static function names(array $items): string
    {
        return implode(', ', array_map(
            fn (array $item) => $item['product_name'] ?? ('#' . $item['product_id']),
            $items
        ));
    }
}
