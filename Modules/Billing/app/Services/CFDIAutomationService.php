<?php

namespace Modules\Billing\Services;

use Modules\Finance\Models\ARInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CFDIAutomationService
{
    /**
     * Auto-generate CFDI from AR Invoice
     *
     * @param ARInvoice $arInvoice
     * @param string|null $formaPago Forma de pago SAT real para una venta de contado
     *                               (PUE) que aun no se cobra
     * @return CFDIInvoice
     * @throws \Exception
     */
    public function generateFromARInvoice(ARInvoice $arInvoice, ?string $formaPago = null): CFDIInvoice
    {
        // Antes de tomar folio: si falta la forma de pago de un PUE, no se gasta.
        [$metodoPago, $formaPagoCfdi] = $this->resolvePaymentTerms($arInvoice, $formaPago);

        return DB::transaction(function () use ($arInvoice, $metodoPago, $formaPagoCfdi) {
            // Get active company settings
            $settings = CompanySetting::where('is_active', true)->firstOrFail();

            // Get customer contact
            $contact = $arInvoice->contact;

            if (!$contact) {
                throw new \Exception('AR Invoice must have a valid contact');
            }

            // Serie y folio de la fuente unica (serie default de ingreso)
            [$serie, $folio] = app(\Modules\Billing\Services\CFDI\CfdiFolioAllocator::class)->allocate($settings, 'I');

            $receptor = \Modules\Billing\Support\CfdiReceptor::fromContact($contact, $settings->postal_code);
            $receptor['receptor_domicilio_fiscal'] = $receptor['receptor_domicilio_fiscal'] ?: '00000';
            $creditDays = (int) ($arInvoice->salesOrder?->credit_days ?? $contact->payment_terms ?? 30);

            // Create CFDI Invoice
            $cfdi = CFDIInvoice::create([
                'company_setting_id' => $settings->id,
                'contact_id' => $contact->id,
                'ar_invoice_id' => $arInvoice->id,

                // Serie and folio
                'series' => $serie,
                'folio' => $folio,
                'fecha_emision' => now(),

                // Receptor (customer): fuente unica en CfdiReceptor. Si el
                // cliente no tiene CP fiscal queda '00000' y el timbrado lo
                // rechaza con detalle, en vez de timbrar con datos falsos.
                ...$receptor,

                // Amounts (convert from decimal to cents if needed)
                'subtotal' => $this->toCents($arInvoice->subtotal),
                'descuento' => 0,
                'iva' => $this->toCents($arInvoice->tax_amount),
                'ieps' => 0,
                'isr_retenido' => 0,
                'iva_retenido' => 0,
                'total' => $this->toCents($arInvoice->total_amount),
                'moneda' => $arInvoice->currency ?? 'MXN',
                'tipo_cambio' => 1.000000,

                // Payment info
                'forma_pago' => $formaPagoCfdi,
                'metodo_pago' => $metodoPago,
                'condiciones_pago' => $metodoPago === 'PPD' ? "Crédito a {$creditDays} días" : null,

                // Type
                'tipo_comprobante' => 'I', // Ingreso

                // Status
                'status' => 'draft',
            ]);

            // Copy items into the CFDI. In the automatic flow (facturar orden,
            // post-pago Stripe) the real items live on the sales order linked
            // to the AR invoice, each one with its product_id.
            $lineNumber = 1;

            foreach ($this->resolveSourceItems($arInvoice) as $item) {
                $cantidad = $item->quantity ?? 1;
                $valorUnitario = $this->toCents($item->unit_price ?? $item->amount ?? 0);
                $importe = (int) ($cantidad * $valorUnitario);

                $product = $item->product;

                // La tasa de IVA sale del producto, NO de una constante: hay
                // productos exentos o tasa 0 (product->iva = false / tax_rate = 0)
                // y hardcodear 0.16 producia un CFDI con Traslado de IVA que el
                // Total no cuadra -> SAT CFDI40119. La tasa se toma como fraccion
                // (tax_rate viene como 16 o 0). El importe se calcula en centavos
                // sobre el importe neto de la linea.
                $ivaRate = $product ? $product->effective_tax_rate / 100 : 0.0;
                $iva = (int) round($importe * $ivaRate);

                CFDIItem::create([
                    'cfdi_invoice_id' => $cfdi->id,
                    'product_id' => $product?->id,
                    'numero_linea' => $lineNumber++,

                    // SAT Catalogs: snapshot the keys configured on the product
                    // when present; otherwise keep the generic defaults. The XML
                    // generator keeps its own product-first fallback on top.
                    'clave_prod_serv' => $product?->sat_clave_prod_serv ?: '01010101', // Generic product/service
                    'clave_unidad' => $product?->sat_clave_unidad ?: 'E48', // Service unit
                    'unidad' => $product?->unit?->name ?? 'Servicio',

                    // Item details
                    'cantidad' => $cantidad,
                    'descripcion' => $item->description ?? $product?->name ?? 'Servicio',
                    'no_identificacion' => $product?->sku,

                    // Amounts
                    'valor_unitario' => $valorUnitario,
                    'importe' => $importe,
                    'descuento' => 0,

                    // Taxes: la tasa y el importe salen del producto. Para tasa 0
                    // (o exento) el traslado va con TasaOCuota 0.000000 e importe 0,
                    // manteniendo ObjetoImp '02' (si objeto, tasa 0) para no
                    // descuadrar el Total (SAT CFDI40119).
                    'objeto_imp' => '02', // Sí objeto de impuesto
                    'impuestos' => [
                        'traslados' => [
                            [
                                'base' => $importe,
                                'impuesto' => '002', // IVA
                                'tipo_factor' => 'Tasa',
                                'tasa_o_cuota' => number_format($ivaRate, 6, '.', ''),
                                'importe' => $iva,
                            ]
                        ],
                        'retenciones' => []
                    ],
                ]);
            }

            Log::info('CFDI auto-generated from AR Invoice', [
                'cfdi_id' => $cfdi->id,
                'ar_invoice_id' => $arInvoice->id,
                'serie' => $serie,
                'folio' => $folio,
            ]);

            return $cfdi;
        });
    }

    /**
     * Auto-generate CFDI from Payment Transaction (after Stripe payment)
     *
     * @param PaymentTransaction $transaction
     * @return CFDIInvoice|null
     */
    public function generateFromPaymentTransaction(PaymentTransaction $transaction): ?CFDIInvoice
    {
        try {
            // Check if transaction is successful
            if ($transaction->status !== 'captured') {
                Log::warning('Attempted to generate CFDI from non-captured transaction', [
                    'transaction_id' => $transaction->id,
                    'status' => $transaction->status,
                ]);
                return null;
            }

            // Get AR Invoice if it exists
            $arInvoice = $transaction->arInvoice;

            if (!$arInvoice) {
                Log::warning('Payment transaction has no AR Invoice', [
                    'transaction_id' => $transaction->id,
                ]);
                return null;
            }

            // Check if CFDI already exists for this AR Invoice
            $existingCFDI = CFDIInvoice::where('ar_invoice_id', $arInvoice->id)->first();

            if ($existingCFDI) {
                Log::info('CFDI already exists for AR Invoice', [
                    'cfdi_id' => $existingCFDI->id,
                    'ar_invoice_id' => $arInvoice->id,
                ]);
                return $existingCFDI;
            }

            // Generate CFDI from AR Invoice (Stripe: la forma sale de la transaccion)
            $mapped = $this->mapPaymentMethodToFormaPago($transaction->payment_method);
            $cfdi = $this->generateFromARInvoice($arInvoice, $mapped !== '99' ? $mapped : null);

            // Update CFDI with payment info
            $cfdi->update([
                'forma_pago' => $this->mapPaymentMethodToFormaPago($transaction->payment_method),
                'metodo_pago' => 'PUE', // Pago en una exhibición (already paid)
            ]);

            Log::info('CFDI auto-generated from Payment Transaction', [
                'cfdi_id' => $cfdi->id,
                'transaction_id' => $transaction->id,
                'payment_method' => $transaction->payment_method,
            ]);

            return $cfdi;

        } catch (\Exception $e) {
            Log::error('Failed to auto-generate CFDI from Payment Transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Resolve the line items to copy into the CFDI.
     *
     * ARInvoice has no items relation of its own: in the automatic flow the
     * items come from the linked sales order (with product_id, so the SAT
     * keys configured on each product can be applied). A preloaded `items`
     * relation on the AR invoice keeps taking precedence (legacy behavior).
     *
     * @return \Illuminate\Support\Collection
     */
    protected function resolveSourceItems(ARInvoice $arInvoice)
    {
        if ($arInvoice->relationLoaded('items')) {
            return collect($arInvoice->getRelation('items'));
        }

        $salesOrder = $arInvoice->salesOrder;

        if ($salesOrder) {
            return $salesOrder->items()->with('product.unit')->get();
        }

        return collect();
    }

    /**
     * MetodoPago y FormaPago del CFDI de ingreso (decision de Gabino 31-10: credito
     * = PPD + REP, contado = PUE):
     * - Factura ya pagada: PUE con la forma del cobro mayor.
     * - Si no, lo que dice el pedido (payment_method, que nace de los dias de
     *   credito); sin pedido, credito del cliente > 0 = PPD.
     * - PPD lleva 99. PUE exige la forma real (el SAT no admite 99 en PUE): la
     *   recibida, la del cobro o un rechazo con mensaje claro.
     *
     * @return array{0: string, 1: string}
     */
    private function resolvePaymentTerms(ARInvoice $arInvoice, ?string $formaPago): array
    {
        $formaPago = $formaPago !== null && $formaPago !== '99' ? $formaPago : null;
        $collected = $this->collectedFormaPago($arInvoice);

        if ($arInvoice->status === 'paid') {
            return ['PUE', $formaPago ?? $collected ?? throw new \DomainException(
                'La factura ya está pagada (PUE) pero su cobro no tiene forma de pago del SAT; indícala para generar el CFDI.'
            )];
        }

        // La factura puede no venir de un pedido: first() tipa el nulo que la propiedad oculta.
        $order = $arInvoice->salesOrder()->first();
        $contact = $arInvoice->contact()->first();
        $metodo = $order !== null ? strtoupper((string) $order->payment_method) : '';
        if (!in_array($metodo, ['PUE', 'PPD'], true)) {
            $creditDays = $order !== null && $order->credit_days !== null
                ? (int) $order->credit_days
                : (int) ($contact !== null ? $contact->payment_terms : 0);
            $metodo = $creditDays > 0 ? 'PPD' : 'PUE';
        }

        if ($metodo === 'PPD') {
            return ['PPD', '99'];
        }

        return ['PUE', $formaPago ?? $collected ?? throw new \DomainException(
            'Es una venta de contado (PUE) y aún no se cobra: indica la forma de pago real (efectivo, transferencia, tarjeta...) para generar el CFDI. "99 Por definir" solo aplica a crédito (PPD).'
        )];
    }

    /** Forma SAT del cobro mayor aplicado a la factura, si la hay. */
    private function collectedFormaPago(ARInvoice $arInvoice): ?string
    {
        $best = null;
        $bestAmount = 0.0;
        foreach ($arInvoice->paymentApplications()->where('is_active', true)->with('payment.paymentMethod')->get() as $app) {
            $clave = $app->payment?->metadata['forma_pago'] ?? $app->payment?->paymentMethod?->code;
            if ($clave && $clave !== '99' && (float) $app->amount > $bestAmount) {
                $best = $clave;
                $bestAmount = (float) $app->amount;
            }
        }

        return $best;
    }

    /**
     * Map payment method to SAT forma_pago
     *
     * @param string|null $paymentMethod
     * @return string
     */
    protected function mapPaymentMethodToFormaPago(?string $paymentMethod): string
    {
        if (!$paymentMethod) {
            return '99'; // Por definir
        }

        return match ($paymentMethod) {
            'card' => '04', // Tarjeta de crédito
            'bank_transfer', 'spei' => '03', // Transferencia electrónica
            'oxxo' => '01', // Efectivo
            default => '99', // Por definir
        };
    }

    /**
     * Convert amount to cents if it's in decimal format
     *
     * @param mixed $amount
     * @return int
     */
    /**
     * Convert amount to cents.
     *
     * Amounts from AR Invoice are always in decimal (e.g., 100.00 = $100 MXN).
     * CFDI stores amounts in cents (e.g., 10000 = $100 MXN).
     */
    protected function toCents($amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
