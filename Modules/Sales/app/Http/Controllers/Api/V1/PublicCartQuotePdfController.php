<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Services\CartQuotePdfService;

/**
 * Cotizacion informativa del carrito publico (pedido del cliente 2026-09-01:
 * el boton Cotizar descarga el PDF directo). Sin auth: el carrito publico es
 * anonimo (localStorage), asi que este endpoint tambien lo es; va con
 * throttle y sin efectos (no persiste nada). La cotizacion formal con folio
 * sigue viviendo en POST /quotes/from-cart (requiere registro).
 */
class PublicCartQuotePdfController extends Controller
{
    public function download(Request $request, CartQuotePdfService $service)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ]);

        $priced = $service->buildLines($validated['items']);

        if (empty($priced['lines'])) {
            return response()->json([
                'message' => 'Ninguno de los productos del carrito esta disponible en el catalogo.',
            ], 422);
        }

        $pdf = $service->render($priced['lines'], $priced['totals']);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="cotizacion-informativa.pdf"',
        ]);
    }
}
