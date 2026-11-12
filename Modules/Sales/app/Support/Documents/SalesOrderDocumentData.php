<?php

namespace Modules\Sales\Support\Documents;

use Illuminate\Database\Eloquent\Model;
use Modules\Billing\Models\CompanySetting;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Support\PdfImageHelper;

/**
 * El `$doc` del constructor de documentos de Atomo (formato v1) para un pedido
 * (orden de venta) de LWM, desde lo que ya preparaba SalesOrderPDFGenerator
 * para su Blade: emisor fiscal, cliente, estado, entrega, partidas con
 * descuento, totales con letra, cuentas bancarias y condiciones (cada una
 * segun su interruptor de CompanySetting), y notas. Sin diseno del Figma: parte
 * del preajuste `sales-order` de fabrica.
 */
final class SalesOrderDocumentData
{
    private const PAYMENT_METHODS = ['PUE' => 'Pago en una sola exhibición', 'PPD' => 'Pago en parcialidades o diferido'];

    /**
     * @param  array<string, mixed>  $prepared  salida de SalesOrderPDFGenerator::prepareData()
     * @return array<string, mixed>
     */
    public static function from(SalesOrder $order, array $prepared): array
    {
        $settings = $prepared['settings'] ?? null;
        $contact = $prepared['contact'] ?? null;
        $totals = (array) ($prepared['totals'] ?? []);
        $currency = (string) ($prepared['currency'] ?? 'MXN');
        $statusLabel = $prepared['statusLabels'][$order->status] ?? ucfirst((string) $order->status);

        $date = $order->getAttribute('order_date') ?? $order->getAttribute('created_at');
        $meta = [
            ['key' => 'date', 'label' => 'Fecha del pedido', 'value' => $date instanceof \DateTimeInterface ? $date->format('d/m/Y') : ''],
            ['key' => 'status', 'label' => 'Estado', 'value' => $statusLabel],
        ];
        if ($order->customer_po_number) {
            $meta[] = ['key' => 'po', 'label' => 'OC del cliente', 'value' => (string) $order->customer_po_number];
        }
        if ($order->payment_method) {
            $meta[] = ['key' => 'payment', 'label' => 'Método de pago', 'value' => self::PAYMENT_METHODS[$order->payment_method] ?? (string) $order->payment_method];
        }
        if ($order->credit_days !== null) {
            $meta[] = ['key' => 'credit', 'label' => 'Días de crédito', 'value' => $order->credit_days.' días'];
        }
        $approved = $order->getAttribute('approved_at');
        if ($approved instanceof \DateTimeInterface) {
            $meta[] = ['key' => 'approved', 'label' => 'Aprobado', 'value' => $approved->format('d/m/Y')];
        }

        $rows = [['label' => 'Subtotal', 'amount' => (float) ($totals['subtotal'] ?? 0)]];
        if ((float) ($totals['discount'] ?? 0) > 0) {
            $rows[] = ['label' => 'Descuento', 'amount' => -1 * (float) $totals['discount']];
        }
        if ((float) ($totals['shipping'] ?? 0) > 0) {
            $rows[] = ['label' => 'Envío', 'amount' => (float) $totals['shipping']];
        }
        $rows[] = ['label' => 'IVA', 'amount' => (float) ($totals['tax'] ?? 0)];

        $doc = [
            'issuer' => self::issuer($settings),
            'title' => ['label' => 'Pedido', 'number' => (string) $order->order_number, 'chip' => $statusLabel],
            'party' => array_filter([
                'label' => 'Cliente',
                'name' => (string) ($contact ? ($contact->getAttribute('name') ?: $contact->getAttribute('legal_name')) : 'Cliente'),
                'taxId' => $contact ? $contact->getAttribute('tax_id') : null,
                'email' => $contact ? $contact->getAttribute('email') : null,
                'phone' => $contact ? $contact->getAttribute('phone') : null,
            ], fn ($v) => $v !== null && $v !== ''),
            'meta' => $meta,
            'lines' => collect($prepared['items'] ?? [])->map(fn (SalesOrderItem $item) => self::line($item))->values()->all(),
            'totals' => [
                'rows' => $rows,
                'total' => (float) ($totals['total'] ?? 0),
                'currency' => $currency,
                'words' => (string) ($prepared['totalInWords'] ?? ''),
            ],
        ];
        $shipping = self::shipping($prepared['shippingAddress'] ?? null);
        if ($shipping) {
            $doc['shipping'] = $shipping;
        }
        if (($prepared['showBankAccounts'] ?? true) && $settings) {
            $doc['bank'] = self::bank($settings);
        }
        if ($prepared['showConditions'] ?? true) {
            $doc['terms'] = array_values(array_filter(array_map('strval', (array) ($prepared['conditions'] ?? []))));
        }
        if ($order->notes) {
            $doc['notes'] = (string) $order->notes;
        }

        return $doc;
    }

    /** @return array<string, mixed> */
    private static function issuer(?CompanySetting $settings): array
    {
        $logoPath = $settings ? $settings->getAttribute('logo_path') : null;
        $logo = $logoPath ? PdfImageHelper::productImageDataUri((string) $logoPath) : null;
        $accent = config('branding.accent') ?: '#0094ff';

        return array_filter([
            'name' => (string) ($settings ? $settings->getAttribute('company_name') : config('app.name')),
            'taxId' => $settings ? $settings->getAttribute('rfc') : null,
            'address' => $settings ? $settings->getAttribute('full_address') : null,
            'phone' => $settings ? $settings->getAttribute('phone') : null,
            'email' => $settings ? $settings->getAttribute('email') : null,
            'logo' => $logo && str_starts_with($logo, 'data:image/') ? $logo : null,
            'accent' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $accent) ? strtolower((string) $accent) : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @return list<array<string, string>> */
    private static function bank(CompanySetting $settings): array
    {
        $out = [];
        foreach ((array) ($settings->getAttribute('bank_accounts') ?? []) as $a) {
            $a = (array) $a;
            $out[] = array_filter([
                'bank' => (string) ($a['bank_name'] ?? $a['bank'] ?? ''),
                'holder' => (string) ($a['beneficiary'] ?? $a['holder'] ?? $settings->getAttribute('company_name') ?? ''),
                'account' => isset($a['account_number']) ? (string) $a['account_number'] : null,
                'clabe' => (string) ($a['clabe'] ?? ''),
                'swift' => isset($a['swift']) ? (string) $a['swift'] : null,
                'currency' => isset($a['currency']) ? (string) $a['currency'] : null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return array_values(array_filter($out, fn ($a) => ($a['bank'] ?? '') !== ''));
    }

    /** @return array<string, string>|null */
    private static function shipping(mixed $s): ?array
    {
        if (! is_array($s) || $s === []) {
            return null;
        }
        $address = trim(implode(', ', array_filter([
            $s['line1'] ?? $s['address_line1'] ?? null,
            $s['line2'] ?? $s['address_line2'] ?? null,
            $s['city'] ?? null,
            $s['state'] ?? null,
            ! empty($s['postal_code']) ? 'CP '.$s['postal_code'] : null,
            $s['country'] ?? null,
        ])));
        if ($address === '') {
            return null;
        }

        return array_filter([
            'label' => 'Entregar en',
            'address' => (! empty($s['name']) ? $s['name'].' · ' : '').$address,
            'contact' => ! empty($s['phone']) ? 'Tel. '.$s['phone'] : null,
        ], fn ($v) => $v !== null);
    }

    /** @return array<string, mixed> */
    private static function line(SalesOrderItem $item): array
    {
        $product = $item->getRelationValue('product');
        $product = $product instanceof Model ? $product : null;
        $unit = $product ? $product->getRelationValue('unit') : null;
        $quantity = (float) $item->quantity;
        $unitPrice = (float) $item->unit_price;
        $discount = (float) ($item->getAttribute('discount') ?? 0);
        $total = $item->getAttribute('total');

        return array_filter([
            'sku' => $item->getAttribute('product_sku') ?: ($product ? $product->getAttribute('sku') : null),
            'description' => (string) ($item->getAttribute('product_name') ?: ($product ? $product->getAttribute('name') : 'Producto')),
            'note' => $item->getAttribute('notes') ?: null,
            'quantity' => $quantity,
            'unit' => $unit instanceof Model ? $unit->getAttribute('name') : null,
            'discount' => $discount > 0 ? $discount : null,
        ], fn ($v) => $v !== null && $v !== '') + [
            'unitPrice' => $unitPrice,
            'total' => $total !== null ? (float) $total : round($quantity * $unitPrice - $discount, 2),
        ];
    }
}
