<?php

namespace Modules\Sales\Services;

use App\Services\TaxCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Billing\Models\CompanySetting;
use Modules\Product\Models\Product;

/**
 * Cotizacion INFORMATIVA del carrito publico (sin login, sin folio, no se
 * persiste). Los precios y el IVA se calculan SIEMPRE aqui desde la tabla
 * products (regla 7: jamas confiar montos del cliente); el visitante solo
 * manda ids y cantidades. La cotizacion FORMAL (con folio, guardada en
 * my-quotes) sigue requiriendo registro: este PDF es el escaparate.
 */
class CartQuotePdfService
{
    public function __construct(private TaxCalculator $taxCalculator)
    {
    }

    /**
     * Prices the requested items against the public catalog scope.
     *
     * @param array<int, array{product_id: int|string, quantity: int|float|string}> $items
     * @return array{lines: array<int, array<string, mixed>>, totals: array{net: float, tax: float, total: float}}
     */
    public function buildLines(array $items): array
    {
        $quantities = [];
        foreach ($items as $item) {
            $id = (int) $item['product_id'];
            // Ids repetidos se acumulan (el carrito local no deberia
            // mandarlos, pero el endpoint es publico).
            $quantities[$id] = ($quantities[$id] ?? 0) + (float) $item['quantity'];
        }

        // Mismo recorte que el catalogo publico (PublicProductSchema::indexQuery):
        // nunca cotizar algo que la tienda no muestra.
        $products = Product::query()
            ->whereIn('id', array_keys($quantities))
            ->where('is_active', true)
            ->where('is_public', true)
            ->whereHas('brand', fn ($q) => $q->where('is_active', true))
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->with(['brand', 'unit'])
            ->orderBy('name')
            ->get();

        $lines = [];
        $netTotal = 0.0;
        $taxTotal = 0.0;
        $grandTotal = 0.0;

        foreach ($products as $product) {
            $quantity = $quantities[$product->id];
            if ($quantity <= 0 || $product->price === null) {
                continue;
            }

            $lineAmount = round((float) $product->price * $quantity, 2);
            $split = $this->taxCalculator->netAndTax($lineAmount, (float) $product->effective_tax_rate);

            $lines[] = [
                'sku' => $product->sku,
                'name' => $product->name,
                'brand' => $product->brand?->name,
                'unit' => $product->unit?->name,
                'quantity' => $quantity,
                'unit_price' => (float) $product->price,
                'net' => $split['net'],
                'tax' => $split['tax'],
                'total' => $split['total'],
            ];

            $netTotal += $split['net'];
            $taxTotal += $split['tax'];
            $grandTotal += $split['total'];
        }

        return [
            'lines' => $lines,
            'totals' => [
                'net' => round($netTotal, 2),
                'tax' => round($taxTotal, 2),
                'total' => round($grandTotal, 2),
            ],
        ];
    }

    /**
     * Renders the informative PDF for the given priced lines.
     */
    public function render(array $lines, array $totals): string
    {
        $company = CompanySetting::getActive();

        $pdf = Pdf::loadView('sales::cart-quote-pdf', [
            'lines' => $lines,
            'totals' => $totals,
            'company' => $company,
            'issuedAt' => now(),
        ])
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->output();
    }
}
