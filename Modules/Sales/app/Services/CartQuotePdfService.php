<?php

namespace Modules\Sales\Services;

use App\Services\TaxCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Product\Models\Product;
use Modules\Sales\Support\QuoteIssuer;

/**
 * Cotizacion INFORMATIVA del carrito publico (sin login, sin folio, no se
 * persiste). Los precios y el IVA se calculan SIEMPRE aqui desde la tabla
 * products (regla 7: jamas confiar montos del cliente); el visitante solo
 * manda ids y cantidades. La cotizacion FORMAL (con folio, guardada en
 * my-quotes) sigue requiriendo registro: este PDF es el escaparate.
 */
class CartQuotePdfService
{
    public function __construct(private TaxCalculator $taxCalculator) {}

    /**
     * Prices the requested items against the public catalog scope.
     *
     * @param  array<int, array{product_id: int|string, quantity: int|float|string}>  $items
     * @return array{lines: array<int, array<string, mixed>>, totals: array{net: float, tax: float, total: float}, pending_count: int}
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
            if ($quantity <= 0) {
                continue;
            }

            $base = [
                'sku' => $product->sku,
                'name' => $product->name,
                'brand' => $product->brand?->name,
                'unit' => $product->unit?->name,
                'quantity' => $quantity,
            ];

            // P2 granel: sujeto a cotizacion (o sin precio) ya no se omite en
            // silencio; va en el PDF como "Precio por confirmar" y no suma.
            if ($product->requiresQuote()) {
                $lines[] = $base + [
                    'unit_price' => null,
                    'net' => 0.0,
                    'tax' => 0.0,
                    'total' => 0.0,
                    'price_pending' => true,
                ];

                continue;
            }

            $lineAmount = round((float) $product->price * $quantity, 2);
            $split = $this->taxCalculator->netAndTax($lineAmount, (float) $product->effective_tax_rate);

            $lines[] = $base + [
                'unit_price' => (float) $product->price,
                'net' => $split['net'],
                'tax' => $split['tax'],
                'total' => $split['total'],
                'price_pending' => false,
            ];

            $netTotal += $split['net'];
            $taxTotal += $split['tax'];
            $grandTotal += $split['total'];
        }

        return [
            'lines' => $lines,
            'pending_count' => count(array_filter($lines, fn ($line) => $line['price_pending'])),
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
    public function render(array $lines, array $totals, int $pendingCount = 0): string
    {
        // Emisor separado de la configuracion fiscal (2026-09-23).
        $company = QuoteIssuer::resolve();

        // Constructor de documentos de Atomo (12-11-2026), detras de su interruptor.
        if (class_exists(\Atomo\Documents\Layout\LayoutRenderer::class)
            && app(\Modules\AppConfig\Services\AppSettingResolver::class)->getBool('documents.builder.cart_quote', false)) {
            return app(\Atomo\Documents\Layout\LayoutRenderer::class)->pdf(
                \Atomo\Documents\Layout\Layouts::get('lwm-cotizacion-carrito'),
                self::document($company, $lines, $totals, $pendingCount),
                app(\Modules\AppConfig\Services\AppSettingResolver::class)->getString('documents.builder.quote_mode', 'color'),
                'letter'
            );
        }

        $pdf = Pdf::loadView('sales::cart-quote-pdf', [
            'lines' => $lines,
            'totals' => $totals,
            'pendingCount' => $pendingCount,
            'company' => $company,
            'issuedAt' => now(),
        ])
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->output();
    }

    /**
     * El `$doc` del constructor para la cotizacion informativa: sin receptor ni
     * folio (no se persiste); las partidas a granel pintan "Por confirmar".
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array{net: float, tax: float, total: float}  $totals
     * @return array<string, mixed>
     */
    public static function document(QuoteIssuer $company, array $lines, array $totals, int $pendingCount = 0): array
    {
        $notices = ['Documento generado desde el carrito del sitio; no constituye una cotización formal ni un comprobante fiscal.'];
        if ($pendingCount > 0) {
            $notices[] = 'Precio por confirmar: '.($pendingCount === 1 ? 'el producto marcado no está incluido' : 'los productos marcados no están incluidos').' en el total.';
        }

        return [
            'issuer' => \Modules\Sales\Support\Documents\QuoteDocumentData::issuer($company),
            'title' => ['label' => 'Cotización informativa', 'number' => now()->format('d/m/Y'), 'chip' => 'Sin folio'],
            'lines' => array_map(fn (array $l) => array_filter([
                'sku' => $l['sku'] ?? null,
                'description' => (string) ($l['name'] ?? 'Producto'),
                'note' => $l['brand'] ?? null,
                'quantity' => (float) $l['quantity'],
                'unit' => $l['unit'] ?? null,
                'priceNote' => ! empty($l['price_pending']) ? 'Por confirmar' : null,
            ], fn ($v) => $v !== null && $v !== '') + [
                'unitPrice' => ! empty($l['price_pending']) ? null : (float) $l['unit_price'],
                'total' => ! empty($l['price_pending']) ? null : (float) $l['net'],
            ], $lines),
            'totals' => [
                'rows' => [['label' => 'Subtotal', 'amount' => (float) $totals['net']], ['label' => 'IVA', 'amount' => (float) $totals['tax']]],
                'total' => (float) $totals['total'],
                'currency' => 'MXN',
                'words' => \Atomo\Documents\Support\AmountInWords::format((float) $totals['total'], 'MXN'),
            ],
            'bank' => \Modules\Sales\Support\Documents\QuoteDocumentData::bank($company, $company->bank_accounts),
            'notices' => $notices,
            'terms' => [
                'Precios vigentes a la fecha de emisión, sujetos a cambio sin previo aviso y a disponibilidad de inventario.',
                'Para una cotización formal con folio, vigencia y condiciones comerciales, regístrese en el sitio y genere su cotización desde el carrito, o contáctenos.',
            ],
        ];
    }
}
