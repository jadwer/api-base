<?php

namespace Modules\Sales\Support\Documents;

use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Support\PdfImageHelper;
use Modules\Sales\Support\QuoteIssuer;

/**
 * Arma el `$doc` del constructor de documentos de Atomo (atomo/documents,
 * formato v1, contrato de la seccion 1b del plan de Atomo) para una cotizacion
 * de LWM. Recibe lo mismo que ya preparaba QuotePDFGenerator para el Blade, asi
 * que emisor, leyendas, cuentas e importe con letra salen de la misma fuente.
 */
final class QuoteDocumentData
{
    /**
     * @param  array<string, mixed>  $prepared  salida de QuotePDFGenerator::prepareData()
     * @return array<string, mixed>
     */
    public static function from(Quote $quote, array $prepared): array
    {
        /** @var QuoteIssuer $company */
        $company = $prepared['company'];
        $contact = $prepared['contact'] ?? null;
        $currency = $quote->currency ?: 'MXN';
        $items = $prepared['items'];

        $meta = [
            ['key' => 'date', 'label' => 'Fecha de creación', 'value' => $quote->created_at->format('d/m/Y')],
            ['key' => 'branch', 'label' => 'Sucursal', 'value' => (string) ($prepared['branch'] ?? 'Matriz')],
        ];
        $seller = $quote->getAttribute('assigned_user_name') ?: ($prepared['elaboratedBy'] ?? null);
        if ($seller) {
            $meta[] = ['key' => 'author', 'label' => 'Vendedor', 'value' => (string) $seller];
        }
        if ($quote->credit_days !== null) {
            $meta[] = ['key' => 'credit', 'label' => 'Días de crédito', 'value' => $quote->credit_days.' días'];
        }

        $rows = [['label' => 'Subtotal', 'amount' => (float) ($quote->subtotal_amount ?? 0)]];
        if ((float) $quote->discount_amount > 0) {
            $rows[] = ['label' => 'Descuento', 'amount' => -1 * (float) $quote->discount_amount];
        }
        $rows[] = ['label' => 'IVA', 'amount' => (float) ($quote->tax_amount ?? 0)];

        $notices = [];
        $pending = (int) ($prepared['pendingCount'] ?? 0);
        if ($pending > 0) {
            $notices[] = 'Precio sujeto a cotización: '.($pending === 1 ? 'el producto marcado no está incluido' : 'los productos marcados no están incluidos')
                .' en el total. Le confirmaremos el precio antes de procesar su pedido.';
        }

        $doc = [
            'issuer' => self::issuer($company),
            'title' => [
                'label' => 'Cotización',
                'number' => (string) $quote->quote_number,
                'chip' => $quote->valid_until ? 'Vigencia: '.$quote->valid_until->format('d/m/Y') : null,
            ],
            'party' => array_filter([
                'label' => 'Receptor',
                'name' => (string) ($contact ? $contact->getAttribute('name') : 'Cliente'),
                'taxId' => $contact?->tax_id,
                'email' => $contact?->email,
            ], fn ($v) => $v !== null && $v !== ''),
            'meta' => $meta,
            'lines' => $items->map(fn (QuoteItem $item) => self::line($item))->values()->all(),
            'totals' => [
                'rows' => $rows,
                'total' => (float) ($quote->total_amount ?? 0),
                'currency' => $currency,
                'words' => (string) ($prepared['amountInWords'] ?? ''),
            ],
            'bank' => self::bank($company, $prepared['bankAccounts'] ?? []),
            'terms' => array_values(array_filter(array_map('strval', (array) ($prepared['conditions'] ?? [])))),
            'notices' => $notices,
        ];
        if ($quote->notes) {
            $doc['notes'] = (string) $quote->notes;
        }
        if (! $doc['title']['chip']) {
            unset($doc['title']['chip']);
        }

        return $doc;
    }

    /** @return array<string, mixed> */
    public static function issuer(QuoteIssuer $company): array
    {
        $address = $company->address
            ? trim(implode("\n", array_filter([
                $company->address['street'] ?? null,
                trim(implode(', ', array_filter([$company->address['city'] ?? null, $company->address['state'] ?? null]))).(! empty($company->address['postal_code']) ? ', CP '.$company->address['postal_code'] : ''),
            ])))
            : null;
        $logo = $company->logo_path ? PdfImageHelper::productImageDataUri($company->logo_path) : null;
        $accent = config('branding.accent') ?: '#0094ff';

        return array_filter([
            'name' => (string) ($company->company_name ?? config('app.name')),
            'taxId' => $company->rfc,
            'address' => $address,
            'phone' => $company->phone,
            'email' => $company->email,
            'logo' => $logo && str_starts_with($logo, 'data:image/') ? $logo : null,
            'accent' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $accent) ? strtolower((string) $accent) : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @return array<string, mixed> */
    private static function line(QuoteItem $item): array
    {
        $product = $item->product;
        $pending = (bool) $item->price_pending;
        $description = (string) ($item->product_name ?: ($product ? $product->getAttribute('name') : 'Producto'));
        $rawNote = $product ? (string) $product->getAttribute('description') : '';
        $note = $rawNote !== '' ? \Illuminate\Support\Str::limit(strip_tags($rawNote), 120) : null;

        return array_filter([
            'sku' => $item->product_sku ?? $product?->sku,
            'description' => $description,
            'note' => $item->notes ? trim(($note ? $note."\n" : '').$item->notes) : $note,
            'quantity' => (float) $item->quantity,
            'unit' => $product?->unit?->name,
            // Granel: precio nulo pinta "Por confirmar", nunca $0.00 (regla del contrato)
            'unitPrice' => $pending ? null : (float) $item->quoted_price,
            'total' => $pending ? null : (float) $item->subtotal_before_discount,
            'priceNote' => $pending ? 'Por confirmar' : null,
        ], fn ($v) => $v !== null && $v !== '') + ['unitPrice' => $pending ? null : (float) $item->quoted_price, 'total' => $pending ? null : (float) $item->subtotal_before_discount];
    }

    /**
     * @param  array<int, array<string, mixed>>  $accounts
     * @return list<array<string, string>>
     */
    public static function bank(QuoteIssuer $company, array $accounts): array
    {
        $out = [];
        foreach ($accounts as $a) {
            $out[] = array_filter([
                'bank' => (string) ($a['bank'] ?? ''),
                'holder' => (string) ($a['holder'] ?? $company->company_name ?? ''),
                'branch' => isset($a['branch']) ? (string) $a['branch'] : null,
                'account' => isset($a['account']) ? (string) $a['account'] : null,
                'clabe' => (string) ($a['clabe'] ?? $a['account_number'] ?? ''),
                'swift' => isset($a['swift']) ? (string) $a['swift'] : null,
                'currency' => isset($a['currency']) ? (string) $a['currency'] : null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return array_values(array_filter($out, fn ($a) => ($a['bank'] ?? '') !== ''));
    }
}
