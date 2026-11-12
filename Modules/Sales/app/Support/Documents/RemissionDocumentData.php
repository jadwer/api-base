<?php

namespace Modules\Sales\Support\Documents;

use Modules\Sales\Models\Remission;
use Modules\Sales\Models\RemissionItem;
use Modules\Sales\Support\PdfImageHelper;

/**
 * El `$doc` del constructor de documentos de Atomo (formato v1) para una
 * remision de LWM, desde lo que ya preparaba RemissionPDFGenerator para su
 * Blade: emisor fiscal (CompanySetting), cliente, entrega, partidas con lote y
 * caducidad, notas de entrega, leyenda y firmas. Sin precios, como hoy.
 */
final class RemissionDocumentData
{
    /**
     * @param  array<string, mixed>  $prepared  salida de RemissionPDFGenerator::prepareData()
     * @return array<string, mixed>
     */
    public static function from(Remission $remission, array $prepared): array
    {
        $company = $prepared['company'] ?? null;
        $contact = $prepared['contact'] ?? null;
        $order = $prepared['order'] ?? null;
        $warehouse = $prepared['warehouse'] ?? null;
        $contactAddress = $prepared['contactAddress'] ?? null;

        $date = $remission->getAttribute('remission_date');
        $meta = [['key' => 'date', 'label' => 'Fecha de salida', 'value' => $date instanceof \DateTimeInterface ? $date->format('d/m/Y') : '']];
        if ($warehouse) {
            $meta[] = ['key' => 'branch', 'label' => 'Almacén', 'value' => (string) $warehouse->getAttribute('name')];
        }
        if ($remission->delivered_by) {
            $meta[] = ['key' => 'author', 'label' => 'Entregó', 'value' => (string) $remission->delivered_by];
        }

        $doc = [
            'issuer' => self::issuer($company, $prepared),
            'title' => array_filter([
                'label' => 'Remisión',
                'number' => (string) $remission->remission_number,
                'chip' => $order ? 'Pedido '.$order->getAttribute('order_number') : null,
            ]),
            'party' => array_filter([
                'label' => 'Cliente',
                'name' => (string) ($contact ? ($contact->getAttribute('name') ?: $contact->getAttribute('legal_name')) : 'Cliente'),
                'taxId' => $contact ? $contact->getAttribute('tax_id') : null,
                'email' => $contact ? $contact->getAttribute('email') : null,
                'address' => $contactAddress ? self::contactAddress($contactAddress) : null,
            ], fn ($v) => $v !== null && $v !== ''),
            'meta' => $meta,
            'lines' => $prepared['items']->map(fn (RemissionItem $item) => self::line($item))->values()->all(),
            'signatures' => [
                ['label' => 'Entregado por', 'name' => $remission->delivered_by ?: 'Nombre, firma y fecha'],
                ['label' => 'Recibido por', 'name' => $remission->received_by ?: 'Nombre, firma y fecha'],
            ],
        ];
        $shipping = self::shipping($prepared['shippingAddress'] ?? null);
        if ($shipping) {
            $doc['shipping'] = $shipping;
        }
        if ($remission->delivery_notes) {
            $doc['notes'] = (string) $remission->delivery_notes;
        }
        $legend = array_values(array_filter(array_map('strval', (array) ($prepared['legendLines'] ?? []))));
        if ($legend !== []) {
            $doc['legal'] = implode(' ', $legend);
        }

        return $doc;
    }

    /** @param  array<string, mixed>  $prepared */
    private static function issuer(?object $company, array $prepared): array
    {
        $addr = $prepared['companyAddress'] ?? null;
        $address = is_array($addr)
            ? trim(implode("\n", array_filter([
                $addr['street'] ?? null,
                trim(implode(', ', array_filter([$addr['city'] ?? null, $addr['state'] ?? null]))).(! empty($addr['postal_code']) ? ', CP '.$addr['postal_code'] : ''),
            ])))
            : null;
        $logoPath = $company ? $company->logo_path ?? null : null;
        $logo = $logoPath ? PdfImageHelper::productImageDataUri((string) $logoPath) : null;
        $accent = config('branding.accent') ?: '#0094ff';

        return array_filter([
            'name' => (string) ($company->company_name ?? config('app.name')),
            'taxId' => $company->rfc ?? null,
            'address' => $address,
            'phone' => $prepared['companyPhone'] ?? null,
            'email' => $prepared['companyEmail'] ?? null,
            'logo' => $logo && str_starts_with($logo, 'data:image/') ? $logo : null,
            'accent' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $accent) ? strtolower((string) $accent) : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private static function contactAddress(object $a): string
    {
        return trim(implode(', ', array_filter([
            $a->street ?? null,
            $a->city ?? null,
            $a->state ?? null,
            ! empty($a->postal_code) ? 'CP '.$a->postal_code : null,
        ])));
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
    private static function line(RemissionItem $item): array
    {
        $product = $item->product;
        $unit = $product ? $product->getRelationValue('unit') : null;
        $note = implode(' · ', array_filter([
            $item->batch_number ? 'Lote '.$item->batch_number : null,
            $item->notes ?: null,
        ]));

        return array_filter([
            'sku' => $item->product_sku ?: ($product ? $product->getAttribute('sku') : null),
            'description' => (string) ($item->product_name ?: ($product ? $product->getAttribute('name') : 'Producto')),
            'note' => $note !== '' ? $note : null,
            'quantity' => (float) $item->quantity,
            'unit' => $item->unit ?: ($unit instanceof \Illuminate\Database\Eloquent\Model ? $unit->getAttribute('name') : null),
            'batch' => $item->batch_number ?: null,
            'expiry' => $item->expiry_date ? $item->expiry_date->format('d/m/Y') : null,
        ], fn ($v) => $v !== null && $v !== '') + ['unitPrice' => null, 'total' => null];
    }
}
