<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * FolioSequence Model
 *
 * Manages configurable document numbering sequences.
 * Supports different formats per document type (quotes, orders, invoices).
 *
 * @property int $id
 * @property string $document_type
 * @property string $prefix
 * @property bool $include_year
 * @property string $year_format
 * @property string $separator
 * @property int $padding
 * @property int $current_sequence
 * @property bool $reset_yearly
 * @property int|null $last_reset_year
 * @property bool $is_active
 */
class FolioSequence extends Model
{
    protected $fillable = [
        'document_type',
        'prefix',
        'include_year',
        'year_format',
        'separator',
        'padding',
        'current_sequence',
        'reset_yearly',
        'last_reset_year',
        'is_active',
    ];

    protected $casts = [
        'include_year' => 'boolean',
        'padding' => 'integer',
        'current_sequence' => 'integer',
        'reset_yearly' => 'boolean',
        'last_reset_year' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the next folio number for a document type
     *
     * Uses database locking to prevent race conditions.
     *
     * @param string $documentType Document type (quote, sales_order, purchase_order, invoice, etc.)
     * @return string The generated folio number
     */
    public static function getNextFolio(string $documentType): string
    {
        return DB::transaction(function () use ($documentType) {
            $sequence = static::where('document_type', $documentType)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                // Fallback to default format if no sequence configured
                return static::generateDefaultFolio($documentType);
            }

            // Check if we need to reset for new year
            $currentYear = (int) now()->format('Y');
            if ($sequence->reset_yearly && $sequence->last_reset_year !== $currentYear) {
                $sequence->current_sequence = 0;
                $sequence->last_reset_year = $currentYear;
            }

            // Increment sequence
            $sequence->current_sequence++;
            $sequence->save();

            return $sequence->formatFolio();
        });
    }

    /**
     * Format the folio number based on configuration
     *
     * @return string
     */
    public function formatFolio(): string
    {
        $parts = [];

        // Add prefix
        if (!empty($this->prefix)) {
            $parts[] = $this->prefix;
        }

        // Add year if configured
        if ($this->include_year) {
            $parts[] = now()->format($this->year_format);
        }

        // Add sequence number with padding
        $parts[] = str_pad($this->current_sequence, $this->padding, '0', STR_PAD_LEFT);

        return implode($this->separator, $parts);
    }

    /**
     * Preview what the next folio would look like without incrementing
     *
     * @return string
     */
    public function previewNextFolio(): string
    {
        $previewSequence = $this->current_sequence + 1;
        $parts = [];

        if (!empty($this->prefix)) {
            $parts[] = $this->prefix;
        }

        if ($this->include_year) {
            $parts[] = now()->format($this->year_format);
        }

        $parts[] = str_pad($previewSequence, $this->padding, '0', STR_PAD_LEFT);

        return implode($this->separator, $parts);
    }

    /**
     * Generate default folio when no sequence is configured
     *
     * @param string $documentType
     * @return string
     */
    protected static function generateDefaultFolio(string $documentType): string
    {
        $prefixes = [
            'quote' => 'COT',
            'sales_order' => 'OV',
            'purchase_order' => 'OC',
            'invoice' => 'FAC',
            'invoice_online' => 'FAC-W',
            'invoice_refac' => 'REFAC',
            'remission' => 'REM',
        ];

        $prefix = $prefixes[$documentType] ?? strtoupper(substr($documentType, 0, 3));
        $year = now()->format('y');
        $timestamp = now()->format('His');

        return sprintf('%s-%s%s', $prefix, $year, $timestamp);
    }

    /**
     * Get sequence by document type
     *
     * @param string $documentType
     * @return static|null
     */
    public static function getByType(string $documentType): ?static
    {
        return static::where('document_type', $documentType)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Update configuration for a document type
     *
     * @param string $documentType
     * @param array $config
     * @return static
     */
    public static function updateConfig(string $documentType, array $config): static
    {
        return static::updateOrCreate(
            ['document_type' => $documentType],
            array_merge($config, ['is_active' => true])
        );
    }

    /**
     * Set initial sequence number (useful for migrating from existing system)
     *
     * @param string $documentType
     * @param int $startFrom
     * @return bool
     */
    public static function setInitialSequence(string $documentType, int $startFrom): bool
    {
        return static::where('document_type', $documentType)
            ->update(['current_sequence' => $startFrom - 1]); // -1 because next call will increment
    }
}
