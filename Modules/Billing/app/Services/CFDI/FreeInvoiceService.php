<?php

namespace Modules\Billing\Services\CFDI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Support\CfdiReceptor;
use Modules\Contacts\Models\Contact;
use Modules\Accounting\Models\Account;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Finance\Services\CreditManagementService;
use Modules\Product\Models\Product;

/**
 * Factura libre, como en Bind (junta 2026-10-08): el usuario elige cliente,
 * serie y conceptos sin pasar por una orden de venta.
 *
 * El borrador no existe fiscal ni contablemente (decision Gabino 23-10): se
 * edita libre y la cuenta por cobrar con su poliza se crea al timbrar
 * (CFDIStampingService llama assertCanPostReceivable antes del PAC y
 * createReceivable despues), asi no hay polizas que revertir por cada
 * correccion del borrador.
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
        private CreditManagementService $creditManagementService,
    ) {
    }

    public const SOURCE = 'free';

    /**
     * @param array{contact_id:int, series?:?string, forma_pago:string, metodo_pago:string, uso_cfdi?:?string,
     *              condiciones_pago?:?string, notes?:?string, items:array<int, array<string, mixed>>} $data
     */
    public function create(array $data): CFDIInvoice
    {
        $settings = $this->activeSettings();
        $draft = $this->buildDraft($settings, $data);

        return DB::transaction(function () use ($settings, $data, $draft) {
            [$serie, $folio] = $this->folioAllocator->allocate($settings, 'I', $data['series'] ?? null);

            $cfdi = CFDIInvoice::create($draft['header'] + [
                'company_setting_id' => $settings->id,
                'series' => $serie,
                'folio' => $folio,
                'tipo_comprobante' => 'I',
                'moneda' => 'MXN',
                'tipo_cambio' => 1,
                'status' => 'draft',
            ]);
            $this->replaceItems($cfdi, $draft['lines']);

            Log::info('Free CFDI draft created', ['cfdi_id' => $cfdi->id, 'serie' => $serie, 'folio' => $folio]);

            return $cfdi->fresh(['items']);
        });
    }

    /**
     * Edita un borrador de factura libre: cliente, pago y conceptos. La serie y
     * el folio no cambian (ya estan asignados).
     */
    public function update(CFDIInvoice $cfdi, array $data): CFDIInvoice
    {
        if (!$this->isFreeDraft($cfdi)) {
            throw new \DomainException('Solo se pueden editar borradores de factura libre sin timbrar.');
        }

        $draft = $this->buildDraft($this->activeSettings(), $data);

        return DB::transaction(function () use ($cfdi, $draft) {
            $cfdi->update($draft['header'] + ['xml_original' => null]);
            $this->replaceItems($cfdi, $draft['lines']);

            return $cfdi->fresh(['items']);
        });
    }

    public function isFreeDraft(CFDIInvoice $cfdi): bool
    {
        return $cfdi->isDraft() && empty($cfdi->uuid) && (($cfdi->metadata['source'] ?? null) === self::SOURCE);
    }

    /** True si el CFDI es factura libre y aun no tiene su cuenta por cobrar. */
    public function needsReceivable(CFDIInvoice $cfdi): bool
    {
        return (($cfdi->metadata['source'] ?? null) === self::SOURCE) && empty($cfdi->ar_invoice_id);
    }

    /**
     * Revisa antes de timbrar que la cuenta por cobrar se pueda crear (credito
     * del cliente y cuentas contables), para no dejar un CFDI timbrado sin
     * cobranza.
     *
     * @throws \DomainException
     */
    public function assertCanPostReceivable(CFDIInvoice $cfdi): void
    {
        foreach (['1104' => 'clientes', '4101' => 'ventas'] as $code => $label) {
            if (!Account::where('code', $code)->where('is_postable', true)->exists()) {
                throw new \DomainException("No existe la cuenta contable de {$label} ({$code}) o no es afectable: no se puede registrar la cobranza de esta factura.");
            }
        }

        $contact = Contact::find($cfdi->contact_id);
        if (!$contact) {
            throw new \DomainException('La factura no tiene cliente.');
        }
        if (config('finance.credit_validation_enabled', true)) {
            try {
                $this->creditManagementService->validateCustomerCredit($contact, $cfdi->total / 100);
            } catch (\Throwable $e) {
                throw new \DomainException($e->getMessage(), 0, $e);
            }
        }
    }

    /** Crea la cuenta por cobrar con su poliza y la liga al CFDI ya timbrado. */
    public function createReceivable(CFDIInvoice $cfdi): void
    {
        if (!$this->needsReceivable($cfdi)) {
            return;
        }

        $contact = Contact::findOrFail($cfdi->contact_id);
        $isPue = $cfdi->metodo_pago === 'PUE';
        $creditDays = (int) ($contact->payment_terms ?: 30);

        $arInvoice = $this->arInvoiceService->createInvoice([
            'contactId' => $contact->id,
            'invoiceDate' => now()->toDateString(),
            'dueDate' => ($isPue ? now() : now()->addDays($creditDays))->toDateString(),
            'currency' => $cfdi->moneda ?: 'MXN',
            'subtotal' => ($cfdi->subtotal - (int) $cfdi->descuento) / 100,
            'taxAmount' => (int) $cfdi->iva / 100,
            'totalAmount' => $cfdi->total / 100,
            'notes' => $cfdi->metadata['notes'] ?? null,
            'metadata' => ['source' => 'free_cfdi', 'cfdi_invoice_id' => $cfdi->id],
        ]);

        $cfdi->update(['ar_invoice_id' => $arInvoice->id]);
    }

    private function activeSettings(): CompanySetting
    {
        $settings = CompanySetting::where('is_active', true)->first();
        if (!$settings) {
            throw new \DomainException('No hay una configuración fiscal activa de la empresa.');
        }

        return $settings;
    }

    /**
     * Encabezado (receptor, pago, totales) y conceptos calculados.
     *
     * @return array{header: array<string, mixed>, lines: list<array<string, mixed>>}
     */
    private function buildDraft(CompanySetting $settings, array $data): array
    {
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

        return [
            'header' => [
                'contact_id' => $contact->id,
                'fecha_emision' => now(),
                ...$receptor,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'iva' => $iva,
                'ieps' => 0,
                'isr_retenido' => 0,
                'iva_retenido' => 0,
                'total' => $total,
                'forma_pago' => $isPue ? $data['forma_pago'] : '99',
                'metodo_pago' => $data['metodo_pago'],
                'condiciones_pago' => ($data['condiciones_pago'] ?? null) ?: ($isPue ? null : "Crédito a {$creditDays} días"),
                'metadata' => array_filter(['source' => self::SOURCE, 'notes' => $data['notes'] ?? null]),
            ],
            'lines' => $lines,
        ];
    }

    /** @param list<array<string, mixed>> $lines */
    private function replaceItems(CFDIInvoice $cfdi, array $lines): void
    {
        CFDIItem::where('cfdi_invoice_id', $cfdi->id)->delete();

        foreach ($lines as $i => $line) {
            CFDIItem::create([
                'cfdi_invoice_id' => $cfdi->id,
                'numero_linea' => $i + 1,
                ...array_diff_key($line, ['iva' => true]),
            ]);
        }
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
