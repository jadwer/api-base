<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\Quote;
use Modules\Billing\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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
     * @param Quote $quote
     * @param array $options Custom options for PDF generation
     * @return string Path to generated PDF
     */
    public function generate(Quote $quote, array $options = []): string
    {
        $data = $this->prepareData($quote, $options);

        $pdf = Pdf::loadView('sales::quote-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', true);

        $filename = $this->generateFilename($quote);
        $path = "quotes/{$quote->id}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Prepare data for PDF template
     *
     * @param Quote $quote
     * @param array $options
     * @return array
     */
    protected function prepareData(Quote $quote, array $options = []): array
    {
        $company = CompanySetting::getActive();

        // Build company address from new fields or fallback to additional_settings
        $companyAddress = null;
        if ($company) {
            if ($company->address || $company->city || $company->state) {
                $companyAddress = [
                    'street' => $company->address,
                    'city' => $company->city,
                    'state' => $company->state,
                    'postal_code' => $company->postal_code,
                ];
            } else {
                $companyAddress = $company->additional_settings['address'] ?? null;
            }
        }

        return [
            'quote' => $quote,
            'items' => $quote->items()->with('product.brand')->get(),
            'totals' => $this->calculateTotals($quote),
            'contact' => $quote->contact,
            'company' => $company,
            'companyAddress' => $companyAddress,
            'companyPhone' => $company?->phone ?? $company?->additional_settings['phone'] ?? null,
            'companyEmail' => $company?->email ?? $company?->additional_settings['email'] ?? null,
            'bankAccounts' => $options['bank_accounts'] ?? $company?->getBankAccounts() ?? $this->defaultBankAccounts,
            'conditions' => $options['conditions'] ?? $company?->getCommercialConditions() ?? $this->defaultConditions,
            'amountInWords' => $this->amountInWords($quote->total_amount, $quote->currency ?? 'MXN'),
        ];
    }

    /**
     * Calculate quote totals
     *
     * @param Quote $quote
     * @return array
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
     *
     * @param Quote $quote
     * @return string
     */
    protected function generateFilename(Quote $quote): string
    {
        $safeNumber = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $quote->quote_number);
        return "cotizacion_{$safeNumber}.pdf";
    }

    /**
     * Download PDF as response
     *
     * @param Quote $quote
     * @param array $options
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
     * @param Quote $quote
     * @param array $options
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
     * @param Quote $quote
     * @param array $options
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stream(Quote $quote, array $options = [])
    {
        $data = $this->prepareData($quote, $options);

        return Pdf::loadView('sales::quote-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', true)
            ->stream($this->generateFilename($quote));
    }

    /**
     * Get existing PDF path or generate new one
     *
     * @param Quote $quote
     * @param array $options
     * @return string
     */
    protected function getOrGeneratePath(Quote $quote, array $options = []): string
    {
        $expectedPath = "quotes/{$quote->id}/" . $this->generateFilename($quote);

        // Always regenerate if options are provided or quote was recently updated
        if (!empty($options) || !Storage::disk('public')->exists($expectedPath)) {
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
     *
     * @param Quote $quote
     * @param array $options
     * @return string
     */
    public function regenerate(Quote $quote, array $options = []): string
    {
        return $this->generate($quote, $options);
    }

    /**
     * Delete generated PDF
     *
     * @param Quote $quote
     * @return bool
     */
    public function delete(Quote $quote): bool
    {
        $path = "quotes/{$quote->id}/" . $this->generateFilename($quote);
        return Storage::disk('public')->delete($path);
    }

    /**
     * Convert amount to words in Spanish
     *
     * @param float $amount
     * @param string $currency
     * @return string
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

        $result = strtoupper($intWords) . ' ' . strtoupper($currencyWord);

        if ($decPart > 0) {
            $result .= ' ' . str_pad($decPart, 2, '0', STR_PAD_LEFT) . '/100';
        } else {
            $result .= ' 00/100';
        }

        return $result . ' ' . strtoupper($currency);
    }

    /**
     * Convert number to Spanish words
     *
     * @param int $number
     * @return string
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
                $result .= $this->numberToWords($millions) . ' millones ';
            }
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            if ($thousands === 1) {
                $result .= 'mil ';
            } else {
                $result .= $this->numberToWords($thousands) . ' mil ';
            }
            $number %= 1000;
        }

        if ($number >= 100) {
            if ($number === 100) {
                $result .= 'cien ';
                $number = 0;
            } else {
                $result .= $hundreds[(int) floor($number / 100)] . ' ';
                $number %= 100;
            }
        }

        if ($number >= 20) {
            $result .= $tens[(int) floor($number / 10)];
            $remainder = $number % 10;
            if ($remainder > 0) {
                $result .= ' y ' . $units[$remainder];
            }
        } elseif ($number >= 10) {
            $result .= $teens[$number - 10];
        } elseif ($number > 0) {
            $result .= $units[$number];
        }

        return trim($result);
    }
}
