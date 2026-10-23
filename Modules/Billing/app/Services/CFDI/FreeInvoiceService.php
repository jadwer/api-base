<?php

namespace Modules\Billing\Services\CFDI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Support\CfdiReceptor;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Product\Models\Product;

/**
 * Factura libre, como en Bind (junta 2026-10-08): el usuario elige cliente,
 * serie y conceptos sin pasar por una orden de venta.
 *
 * Para no dejar una factura sin cobranza ni contabilidad, crea en la misma
 * transaccion la cuenta por cobrar (ARInvoiceService, con su poliza) y el
 * CFDI en borrador ligado a ella; el timbrado sigue el camino normal.
 */
class FreeInvoiceService
{
    /** Tasas de IVA que acepta un concepto; null = no objeto de impuesto. */
    public const TASAS_IVA = [
        '0.160000' => 'IVA 16%',
        '0.080000' => 'IVA 8% (región fronteriza)',
        '0.000000' => 'IVA 0%',
        'no_objeto' => 'No objeto de impuesto',
    ];

    public const METODOS_PAGO = [
        'PUE' => 'PUE - Pago en una sola exhibición',
        'PPD' => 'PPD - Pago en parcialidades o diferido',
    ];

    public function __construct(
        private ARInvoiceService $arInvoiceService,
        private CfdiFolioAllocator $folioAllocator,
    ) {
    }

    /**
     * @param array{contact_id:int, series?:?string, forma_pago:string, metodo_pago:string, uso_cfdi?:?string,
     *              condiciones_pago?:?string, notes?:?string, items:array<int, array<string, mixed>>} $data
     */
    public function create(array $data): CFDIInvoice
    {
        $settings = CompanySetting::where('is_active', true)->first();
        if (!$settings) {
            throw new \DomainException('No hay una configuración fiscal activa de la empresa.');
        }

        $contact = Contact::with('contactAddresses')->findOrFail($data['contact_id']);
        $lines = array_map(fn (array $item) => $this->buildLine($item), array_values($data['items']));

        $subtotal = array_sum(array_column($lines, 'importe'));
        $descuento = array_sum(array_column($lines, 'descuento'));
        $iva = array_sum(array_column($lines, 'iva'));
        $total = $subtotal - $descuento + $iva;

        if ($total <= 0) {
            throw new \DomainException('El total de la factura debe ser mayor a cero.');
        }

        $receptor = CfdiReceptor::fromContact($contact, $settings->postal_code);
        if (!empty($data['uso_cfdi']) && $receptor['receptor_rfc'] !== CfdiReceptor::GENERIC_RFC) {
            $receptor['receptor_uso_cfdi'] = $data['uso_cfdi'];
        }
        if (empty($receptor['receptor_domicilio_fiscal'])) {
            throw new \DomainException('El cliente no tiene código postal fiscal: captura su dirección fiscal antes de facturar.');
        }

        $isPue = $data['metodo_pago'] === 'PUE';
        $creditDays = (int) ($contact->payment_terms ?: 30);

        return DB::transaction(function () use ($data, $settings, $contact, $lines, $subtotal, $descuento, $iva, $total, $receptor, $isPue, $creditDays) {
            $arInvoice = $this->arInvoiceService->createInvoice([
                'contactId' => $contact->id,
                'invoiceDate' => now()->toDateString(),
                'dueDate' => ($isPue ? now() : now()->addDays($creditDays))->toDateString(),
                'currency' => 'MXN',
                'subtotal' => ($subtotal - $descuento) / 100,
                'taxAmount' => $iva / 100,
                'totalAmount' => $total / 100,
                'notes' => $data['notes'] ?? null,
                'metadata' => ['source' => 'free_cfdi'],
            ]);

            [$serie, $folio] = $this->folioAllocator->allocate($settings, 'I', $data['series'] ?? null);

            $cfdi = CFDIInvoice::create([
                'company_setting_id' => $settings->id,
                'contact_id' => $contact->id,
                'ar_invoice_id' => $arInvoice->id,
                'series' => $serie,
                'folio' => $folio,
                'fecha_emision' => now(),
                'tipo_comprobante' => 'I',
                ...$receptor,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'iva' => $iva,
                'ieps' => 0,
                'isr_retenido' => 0,
                'iva_retenido' => 0,
                'total' => $total,
                'moneda' => 'MXN',
                'tipo_cambio' => 1,
                'forma_pago' => $isPue ? $data['forma_pago'] : '99',
                'metodo_pago' => $data['metodo_pago'],
                'condiciones_pago' => $data['condiciones_pago'] ?? ($isPue ? null : "Crédito a {$creditDays} días"),
                'status' => 'draft',
                'metadata' => ['source' => 'free'],
            ]);

            foreach ($lines as $i => $line) {
                CFDIItem::create([
                    'cfdi_invoice_id' => $cfdi->id,
                    'numero_linea' => $i + 1,
                    ...array_diff_key($line, ['iva' => true]),
                ]);
            }

            Log::info('Free CFDI created', ['cfdi_id' => $cfdi->id, 'ar_invoice_id' => $arInvoice->id, 'serie' => $serie, 'folio' => $folio]);

            return $cfdi->fresh(['items']);
        });
    }

    /** Importes del concepto en centavos; la base del IVA es importe menos descuento. */
    protected function buildLine(array $item): array
    {
        $product = !empty($item['product_id']) ? Product::find($item['product_id']) : null;

        $cantidad = (float) $item['cantidad'];
        $valorUnitario = (int) round((float) $item['valor_unitario'] * 100);
        $importe = (int) round($cantidad * $valorUnitario);
        $descuento = (int) round((float) ($item['descuento'] ?? 0) * 100);

        if ($descuento > $importe) {
            throw new \DomainException("El descuento del concepto \"{$item['descripcion']}\" es mayor que su importe.");
        }

        $tasa = (string) $item['tasa_iva'];
        $base = $importe - $descuento;
        $noObjeto = $tasa === 'no_objeto';
        $ivaImporte = $noObjeto ? 0 : (int) round($base * (float) $tasa);

        return [
            'product_id' => $product?->id,
            'clave_prod_serv' => $item['clave_prod_serv'],
            'clave_unidad' => $item['clave_unidad'],
            'unidad' => $item['unidad'] ?? $product?->unit()->value('name'),
            'cantidad' => $cantidad,
            'descripcion' => $item['descripcion'],
            'no_identificacion' => $item['no_identificacion'] ?? $product?->sku,
            'valor_unitario' => $valorUnitario,
            'importe' => $importe,
            'descuento' => $descuento,
            'objeto_imp' => $noObjeto ? '01' : '02',
            'impuestos' => $noObjeto ? [] : [
                'traslados' => [[
                    'base' => $base,
                    'impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa_o_cuota' => $tasa,
                    'importe' => $ivaImporte,
                ]],
                'retenciones' => [],
            ],
            'iva' => $ivaImporte,
        ];
    }
}
