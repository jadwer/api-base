<?php

namespace Modules\Sales\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Models\DocumentLegend;
use Modules\Billing\Services\DocumentLegendRenderer;
use Modules\Sales\Models\Quote;
use Modules\Sales\Support\QuoteIssuer;

/**
 * Generate PDF documents for Quotes
 *
 * Creates professional quote PDFs with company branding,
 * product details, and commercial conditions.
 */
class QuotePDFGenerator
{
    /**
     * Default commercial conditions
     */
    protected array $defaultConditions = [
        'Precios expresados en moneda nacional (MXN) mas IVA.',
        'Tiempo de entrega sujeto a disponibilidad de inventario.',
        'Cotizacion valida por 30 dias a partir de la fecha de emision.',
        'Forma de pago: 50% anticipo, 50% contra entrega.',
        'Los precios pueden variar sin previo aviso despues de la vigencia.',
    ];

    /**
     * Default bank accounts
     */
    protected array $defaultBankAccounts = [];

    /**
     * Generate PDF for quote
     *
     * @param  array  $options  Custom options for PDF generation
     * @return string Path to generated PDF
     */
    public function generate(Quote $quote, array $options = []): string
    {
        $data = $this->prepareData($quote, $options);

        $filename = $this->generateFilename($quote);
        $path = "quotes/{$quote->id}/{$filename}";

        Storage::disk('public')->put($path, $this->render($quote, $data));

        return $path;
    }

    /**
     * Bytes del PDF. Con `documents.builder.quote` encendido (app_settings) se
     * pinta con el constructor de documentos de Atomo y el preajuste
     * `lwm-cotizacion` (diseno del Figma); apagado, con la plantilla Blade de
     * siempre. Asi se prende o se regresa sin desplegar.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(Quote $quote, array $data): string
    {
        if (self::builderEnabled()) {
            return app(\Atomo\Documents\Layout\LayoutRenderer::class)->pdf(
                \Modules\Sales\Support\Documents\DocumentDesigns::layout('quote'),
                \Modules\Sales\Support\Documents\QuoteDocumentData::from($quote, $data),
                null, // el color lo trae el diseno (theme.mode)
                'letter'
            );
        }

        return Pdf::loadView('sales::quote-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->output();
    }

    /**
     * El `$doc` del constructor para esta cotizacion (vista previa del editor).
     *
     * @return array<string, mixed>
     */
    public function builderDocument(Quote $quote): array
    {
        return \Modules\Sales\Support\Documents\QuoteDocumentData::from($quote, $this->prepareData($quote));
    }

    public static function builderEnabled(): bool
    {
        return class_exists(\Atomo\Documents\Layout\LayoutRenderer::class)
            && app(\Modules\AppConfig\Services\AppSettingResolver::class)->getBool('documents.builder.quote', false);
    }

    /**
     * Prepare data for PDF template
     */
    protected function prepareData(Quote $quote, array $options = []): array
    {
        // Emisor de la cotizacion: separado de la configuracion fiscal (2026-09-23).
        $company = QuoteIssuer::resolve();
        $companyAddress = $company->address;

        // Load contact with addresses
        $contact = $quote->contact;
        $contactAddress = null;
        if ($contact && $contact->relationLoaded('contactAddresses')) {
            $contactAddress = $contact->contactAddresses->first();
        } elseif ($contact) {
            $contactAddress = $contact->contactAddresses()->where('is_default', true)->first()
                ?? $contact->contactAddresses()->first();
        }

        // Get the salesperson name from metadata or options
        $elaboratedBy = $quote->metadata['elaborated_by'] ?? null;

        // Sucursal real del documento (multi-sucursal 2026-09); metadata y
        // options quedan como respaldo para cotizaciones previas sin branch_id.
        $branch = $quote->branch?->name ?? $quote->metadata['branch'] ?? $options['branch'] ?? 'Matriz';

        // Leyenda configurable por tipo de documento (fallback: commercial_conditions -> defaults)
        $legendLines = app(DocumentLegendRenderer::class)->render(DocumentLegend::TYPE_QUOTE, [
            'folio' => $quote->quote_number,
            'fecha_emision' => $quote->created_at?->format('d/m/Y'),
            'fecha_vencimiento' => $quote->valid_until?->format('d/m/Y'),
            'total' => '$'.number_format((float) $quote->total_amount, 2).' '.($quote->currency ?? 'MXN'),
            'total_letra' => $this->amountInWords((float) $quote->total_amount, $quote->currency ?? 'MXN'),
            'cliente' => $contact?->name,
            'rfc_cliente' => $contact?->tax_id,
            'empresa' => $company->company_name,
            'dias_credito' => $quote->credit_days ?? $contact?->payment_terms,
        ]);

        $items = $quote->items()->with(['product.brand', 'product.unit'])->get();

        return [
            'quote' => $quote,
            'items' => $items,
            // P2 granel: lineas sujetas a cotizacion sin precio capturado; el
            // PDF las imprime "Por confirmar" con leyenda (decision D1).
            'pendingCount' => $items->filter(fn ($item) => $item->price_pending)->count(),
            'totals' => $this->calculateTotals($quote),
            'contact' => $contact,
            'contactAddress' => $contactAddress,
            'company' => $company,
            'companyAddress' => $companyAddress,
            'companyPhone' => $company->phone,
            'companyEmail' => $company->email,
            'bankAccounts' => $options['bank_accounts'] ?? ($company->bank_accounts ?: $this->defaultBankAccounts),
            'conditions' => $options['conditions'] ?? $legendLines ?? $company->conditions ?? $this->defaultConditions,
            'amountInWords' => $this->amountInWords($quote->total_amount, $quote->currency ?? 'MXN'),
            'elaboratedBy' => $options['elaborated_by'] ?? $elaboratedBy,
            'branch' => $branch,
        ];
    }

    /**
     * Calculate quote totals
     */
    protected function calculateTotals(Quote $quote): array
    {
        return [
            'subtotal' => $quote->subtotal_amount ?? 0,
            'tax' => $quote->tax_amount ?? 0,
            'discount' => $quote->discount_amount ?? 0,
            'total' => $quote->total_amount ?? 0,
        ];
    }

    /**
     * Generate filename for PDF
     */
    protected function generateFilename(Quote $quote): string
    {
        $safeNumber = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $quote->quote_number);

        return "cotizacion_{$safeNumber}.pdf";
    }

    /**
     * Download PDF as response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Quote $quote, array $options = [])
    {
        $path = $this->getOrGeneratePath($quote, $options);
        $filename = $this->generateFilename($quote);

        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * Stream PDF inline (for preview)
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function preview(Quote $quote, array $options = [])
    {
        $path = $this->getOrGeneratePath($quote, $options);

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
            ]
        );
    }

    /**
     * Stream PDF directly without storing (for real-time generation)
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stream(Quote $quote, array $options = [])
    {
        $data = $this->prepareData($quote, $options);

        return response($this->render($quote, $data), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->generateFilename($quote).'"',
        ]);
    }

    /**
     * Get existing PDF path or generate new one
     */
    protected function getOrGeneratePath(Quote $quote, array $options = []): string
    {
        $expectedPath = "quotes/{$quote->id}/".$this->generateFilename($quote);

        // Always regenerate if options are provided or quote was recently updated
        if (! empty($options) || ! Storage::disk('public')->exists($expectedPath)) {
            return $this->generate($quote, $options);
        }

        // Check if PDF is older than last quote update
        $pdfLastModified = Storage::disk('public')->lastModified($expectedPath);
        if ($quote->updated_at && $quote->updated_at->timestamp > $pdfLastModified) {
            return $this->generate($quote, $options);
        }

        return $expectedPath;
    }

    /**
     * Regenerate PDF (force new generation)
     */
    public function regenerate(Quote $quote, array $options = []): string
    {
        return $this->generate($quote, $options);
    }

    /**
     * Delete generated PDF
     */
    public function delete(Quote $quote): bool
    {
        $path = "quotes/{$quote->id}/".$this->generateFilename($quote);

        return Storage::disk('public')->delete($path);
    }

    /**
     * Convert amount to words in Spanish
     */
    protected function amountInWords(float $amount, string $currency = 'MXN'): string
    {
        $currencyNames = [
            'MXN' => ['peso', 'pesos', 'centavo', 'centavos'],
            'USD' => ['dolar', 'dolares', 'centavo', 'centavos'],
            'EUR' => ['euro', 'euros', 'centimo', 'centimos'],
        ];

        $names = $currencyNames[$currency] ?? $currencyNames['MXN'];

        $intPart = (int) floor($amount);
        $decPart = (int) round(($amount - $intPart) * 100);

        $intWords = $this->numberToWords($intPart);
        $currencyWord = $intPart === 1 ? $names[0] : $names[1];

        $result = strtoupper($intWords).' '.strtoupper($currencyWord);

        if ($decPart > 0) {
            $result .= ' '.str_pad($decPart, 2, '0', STR_PAD_LEFT).'/100';
        } else {
            $result .= ' 00/100';
        }

        return $result.' '.strtoupper($currency);
    }

    /**
     * Convert number to Spanish words
     */
    protected function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'cero';
        }

        $units = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $tens = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $teens = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciseis', 'diecisiete', 'dieciocho', 'diecinueve'];
        $hundreds = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        $result = '';

        if ($number >= 1000000) {
            $millions = (int) floor($number / 1000000);
            if ($millions === 1) {
                $result .= 'un millon ';
            } else {
                $result .= $this->numberToWords($millions).' millones ';
            }
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            if ($thousands === 1) {
                $result .= 'mil ';
            } else {
                $result .= $this->numberToWords($thousands).' mil ';
            }
            $number %= 1000;
        }

        if ($number >= 100) {
            if ($number === 100) {
                $result .= 'cien ';
                $number = 0;
            } else {
                $result .= $hundreds[(int) floor($number / 100)].' ';
                $number %= 100;
            }
        }

        if ($number >= 20) {
            $result .= $tens[(int) floor($number / 10)];
            $remainder = $number % 10;
            if ($remainder > 0) {
                $result .= ' y '.$units[$remainder];
            }
        } elseif ($number >= 10) {
            $result .= $teens[$number - 10];
        } elseif ($number > 0) {
            $result .= $units[$number];
        }

        return trim($result);
    }
}
