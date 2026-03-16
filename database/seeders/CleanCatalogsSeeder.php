<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * CleanCatalogsSeeder
 *
 * Creates essential catalog data for a clean project:
 * - Units of measurement
 * - Currencies
 * - Payment methods
 * - Fiscal periods
 * - Chart of accounts (Mexican standard)
 * - Default warehouse
 * - Folio sequences (for quotes, orders, remissions)
 * - Invoice series
 * - Test category, brand, and products
 */
class CleanCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUnits();
        $this->seedCurrencies();
        $this->seedPaymentMethods();
        $this->seedFiscalPeriods();
        $this->seedChartOfAccounts();
        $this->seedJournals();
        $this->seedDefaultWarehouse();
        $this->seedFolioSequences();
        $this->seedDefaultCompanySetting();
        $this->seedInvoiceSeries();
        $this->seedTestCategoryAndBrand();
        $this->seedTestProducts();

        // App settings (company, branding, auth)
        $this->call(\Modules\AppConfig\Database\Seeders\AppSettingSeeder::class);

        // Static pages (nosotros, laboratorios, certificados, etc.)
        $this->call(\Modules\PageBuilder\Database\Seeders\StaticPageSeeder::class);

        $this->command->info('  - Essential catalogs created');
    }

    /**
     * Seed units of measurement (SAT codes as codes for CFDI compatibility)
     */
    private function seedUnits(): void
    {
        $units = [
            ['unit_type' => 'unit', 'code' => 'H87', 'name' => 'Pieza'],
            ['unit_type' => 'unit', 'code' => 'XBX', 'name' => 'Caja'],
            ['unit_type' => 'unit', 'code' => 'XPK', 'name' => 'Paquete'],
            ['unit_type' => 'weight', 'code' => 'KGM', 'name' => 'Kilogramo'],
            ['unit_type' => 'weight', 'code' => 'GRM', 'name' => 'Gramo'],
            ['unit_type' => 'volume', 'code' => 'LTR', 'name' => 'Litro'],
            ['unit_type' => 'volume', 'code' => 'MLT', 'name' => 'Mililitro'],
            ['unit_type' => 'volume', 'code' => 'GLL', 'name' => 'Galón'],
            ['unit_type' => 'length', 'code' => 'MTR', 'name' => 'Metro'],
            ['unit_type' => 'length', 'code' => 'CMT', 'name' => 'Centímetro'],
            ['unit_type' => 'service', 'code' => 'E48', 'name' => 'Servicio'],
            ['unit_type' => 'service', 'code' => 'ACT', 'name' => 'Actividad'],
        ];

        foreach ($units as $unit) {
            \Modules\Product\Models\Unit::firstOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }
    }

    /**
     * Seed currencies (MXN as default)
     */
    private function seedCurrencies(): void
    {
        $currencies = [
            [
                'code' => 'MXN',
                'name' => 'Peso Mexicano',
                'symbol' => '$',
                'exchange_rate' => 1.0000,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'code' => 'USD',
                'name' => 'Dólar Estadounidense',
                'symbol' => 'US$',
                'exchange_rate' => 17.5000, // Adjust as needed
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 19.0000, // Adjust as needed
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            \Modules\Ecommerce\Models\Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }

    /**
     * Seed payment methods (SAT compliant - code is the SAT code)
     */
    private function seedPaymentMethods(): void
    {
        $methods = [
            ['code' => '01', 'name' => 'Efectivo', 'type' => 'cash', 'is_active' => true],
            ['code' => '02', 'name' => 'Cheque nominativo', 'type' => 'check', 'is_active' => true],
            ['code' => '03', 'name' => 'Transferencia electrónica de fondos', 'type' => 'transfer', 'is_active' => true],
            ['code' => '04', 'name' => 'Tarjeta de crédito', 'type' => 'credit_card', 'is_active' => true],
            ['code' => '28', 'name' => 'Tarjeta de débito', 'type' => 'debit_card', 'is_active' => true],
            ['code' => '99', 'name' => 'Por definir', 'type' => 'other', 'is_active' => true],
        ];

        foreach ($methods as $method) {
            \Modules\Finance\Models\PaymentMethod::firstOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }

    /**
     * Seed fiscal periods for current year
     */
    private function seedFiscalPeriods(): void
    {
        $year = (int) date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();

            \Modules\Accounting\Models\FiscalPeriod::firstOrCreate(
                [
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'name' => sprintf('%d-%02d', $year, $month),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'status' => 'open',
                    'closed_at' => null,
                    'closed_by_id' => null,
                    'closing_entry_id' => null,
                    'metadata' => [],
                ]
            );
        }
    }

    /**
     * Seed Mexican chart of accounts (calls existing seeder)
     */
    private function seedChartOfAccounts(): void
    {
        // Use the existing Mexican chart of accounts seeder
        $this->callSilent([
            \Modules\Accounting\Database\Seeders\CatalogoCuentasMexicanoSeeder::class,
        ]);
    }

    /**
     * Seed standard accounting journals (AR, AP, GL, CASH, BANK)
     */
    private function seedJournals(): void
    {
        $journals = [
            ['code' => 'AR', 'name' => 'Accounts Receivable', 'description' => 'Journal for customer invoices and payments'],
            ['code' => 'AP', 'name' => 'Accounts Payable', 'description' => 'Journal for supplier invoices and payments'],
            ['code' => 'GL', 'name' => 'General Ledger', 'description' => 'General journal entries'],
            ['code' => 'CASH', 'name' => 'Cash Receipts', 'description' => 'Cash receipt transactions'],
            ['code' => 'BANK', 'name' => 'Bank Transactions', 'description' => 'Bank-related transactions'],
        ];

        foreach ($journals as $journalData) {
            \Modules\Accounting\Models\Journal::firstOrCreate(
                ['code' => $journalData['code']],
                [
                    'name' => $journalData['name'],
                    'prefix' => $journalData['code'],
                    'type' => 'general',
                    'description' => $journalData['description'],
                    'status' => 'active',
                    'metadata' => ['created_by' => 'CleanCatalogsSeeder'],
                ]
            );
        }
    }

    /**
     * Seed default warehouse
     */
    private function seedDefaultWarehouse(): void
    {
        \Modules\Inventory\Models\Warehouse::firstOrCreate(
            ['code' => 'WH-001'],
            [
                'name' => 'Almacén Principal',
                'slug' => 'almacen-principal',
                'description' => 'Almacén principal de la empresa',
                'warehouse_type' => 'main',
                'address' => '', // To be configured
                'city' => '',
                'state' => '',
                'country' => 'México',
                'postal_code' => '',
                'phone' => '',
                'email' => '',
                'manager_name' => '',
                'max_capacity' => 10000.00,
                'capacity_unit' => 'm3',
                'is_active' => true,
                'operating_hours' => ['mon-fri' => '08:00-18:00', 'sat' => '08:00-14:00'],
            ]
        );
    }

    /**
     * Seed default company setting (required for invoice series)
     */
    private function seedDefaultCompanySetting(): void
    {
        \Modules\Billing\Models\CompanySetting::firstOrCreate(
            ['rfc' => 'EKU9003173C9'], // RFC de pruebas SAT (persona moral, 12 chars)
            [
                'company_name' => 'ESCUELA KEMPER URGATE SA DE CV',
                'tax_regime' => '601', // General de Ley Personas Morales
                'postal_code' => '42501',
                'invoice_series' => 'FAC',
                'credit_note_series' => 'N',
                'next_invoice_folio' => 1,
                'next_credit_note_folio' => 1,
                'pac_provider' => 'sw',
                'pac_username' => env('SW_PAC_USERNAME', ''),
                'pac_password' => env('SW_PAC_PASSWORD', ''),
                'pac_production_mode' => false,
                'is_active' => true,
                'address' => 'Av. de las Americas 1254',
                'city' => 'Pachuca de Soto',
                'state' => 'Hidalgo',
                'phone' => '7711234567',
                'email' => 'facturacion@laborwasser.com',
            ]
        );
    }

    /**
     * Seed invoice series for CFDI
     */
    private function seedInvoiceSeries(): void
    {
        // Get the default company setting
        $companySetting = \Modules\Billing\Models\CompanySetting::where('is_active', true)->first();

        if (!$companySetting) {
            return; // Skip if no company setting exists
        }

        $series = [
            [
                'company_setting_id' => $companySetting->id,
                'code' => 'FAC',
                'name' => 'Facturacion Normal',
                'description' => 'Serie principal de facturacion',
                'cfdi_type' => 'I',
                'current_folio' => 0,
                'initial_folio' => 1,
                'folio_padding' => 6,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'company_setting_id' => $companySetting->id,
                'code' => 'FAC-W',
                'name' => 'Facturacion Web',
                'description' => 'Serie para ventas en linea',
                'cfdi_type' => 'I',
                'current_folio' => 0,
                'initial_folio' => 1,
                'folio_padding' => 6,
                'is_active' => true,
                'is_default' => false,
                'source_type' => 'web',
            ],
            [
                'company_setting_id' => $companySetting->id,
                'code' => 'N',
                'name' => 'Notas de Credito',
                'description' => 'Serie para notas de credito',
                'cfdi_type' => 'E',
                'current_folio' => 0,
                'initial_folio' => 1,
                'folio_padding' => 6,
                'is_active' => true,
                'is_default' => true,
            ],
        ];

        foreach ($series as $s) {
            \Modules\Billing\Models\InvoiceSeries::firstOrCreate(
                ['company_setting_id' => $s['company_setting_id'], 'code' => $s['code']],
                $s
            );
        }
    }

    /**
     * Seed folio sequences for quotes, orders, remissions
     */
    private function seedFolioSequences(): void
    {
        $sequences = [
            [
                'document_type' => 'quote',
                'prefix' => 'COT',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '-',
                'padding' => 5,
                'current_sequence' => 0,
                'reset_yearly' => true,
                'last_reset_year' => (int) date('Y'),
                'is_active' => true,
            ],
            [
                'document_type' => 'sales_order',
                'prefix' => 'OV',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '-',
                'padding' => 5,
                'current_sequence' => 0,
                'reset_yearly' => true,
                'last_reset_year' => (int) date('Y'),
                'is_active' => true,
            ],
            [
                'document_type' => 'purchase_order',
                'prefix' => 'OC',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '-',
                'padding' => 5,
                'current_sequence' => 0,
                'reset_yearly' => true,
                'last_reset_year' => (int) date('Y'),
                'is_active' => true,
            ],
            [
                'document_type' => 'remission',
                'prefix' => 'REM',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '-',
                'padding' => 5,
                'current_sequence' => 0,
                'reset_yearly' => true,
                'last_reset_year' => (int) date('Y'),
                'is_active' => true,
            ],
            [
                'document_type' => 'fractionation',
                'prefix' => 'FRAC',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '-',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'last_reset_year' => null,
                'is_active' => true,
            ],
        ];

        foreach ($sequences as $seq) {
            \Modules\Sales\Models\FolioSequence::firstOrCreate(
                ['document_type' => $seq['document_type']],
                $seq
            );
        }
    }

    /**
     * Seed test category and brand (can be deleted easily)
     */
    private function seedTestCategoryAndBrand(): void
    {
        // Test Category
        \Modules\Product\Models\Category::firstOrCreate(
            ['slug' => 'productos-prueba'],
            [
                'name' => 'Productos de Prueba',
                'description' => 'Categoria para productos de prueba. Puede eliminarse.',
                'slug' => 'productos-prueba',
            ]
        );

        // Test Brand
        \Modules\Product\Models\Brand::firstOrCreate(
            ['slug' => 'marca-prueba'],
            [
                'name' => 'Marca Prueba',
                'description' => 'Marca para productos de prueba. Puede eliminarse.',
                'slug' => 'marca-prueba',
            ]
        );
    }

    /**
     * Seed 5 test products with placeholder images and PDFs
     */
    private function seedTestProducts(): void
    {
        // Get required relations
        $unit = \Modules\Product\Models\Unit::where('code', 'H87')->first(); // Pieza
        $category = \Modules\Product\Models\Category::where('slug', 'productos-prueba')->first();
        $brand = \Modules\Product\Models\Brand::where('slug', 'marca-prueba')->first();

        if (!$unit || !$category || !$brand) {
            return; // Skip if dependencies don't exist
        }

        // Create storage directories if they don't exist
        Storage::disk('public')->makeDirectory('products');
        Storage::disk('public')->makeDirectory('datasheets');

        $products = [
            [
                'name' => 'Producto de Prueba 1',
                'sku' => 'TEST-001',
                'description' => 'Producto de prueba para desarrollo. Puede eliminarse.',
                'full_description' => 'Este es un producto de prueba creado por el seeder. Puede eliminarse sin afectar el sistema.',
                'price' => 100.00,
                'cost' => 50.00,
                'iva' => true,
            ],
            [
                'name' => 'Producto de Prueba 2',
                'sku' => 'TEST-002',
                'description' => 'Segundo producto de prueba para desarrollo.',
                'full_description' => 'Este es el segundo producto de prueba. Ideal para probar funcionalidades de carrito y cotizaciones.',
                'price' => 250.00,
                'cost' => 125.00,
                'iva' => true,
            ],
            [
                'name' => 'Producto de Prueba 3',
                'sku' => 'TEST-003',
                'description' => 'Tercer producto de prueba sin IVA.',
                'full_description' => 'Producto de prueba configurado sin IVA para probar calculos de impuestos.',
                'price' => 500.00,
                'cost' => 300.00,
                'iva' => false,
            ],
            [
                'name' => 'Producto de Prueba 4',
                'sku' => 'TEST-004',
                'description' => 'Cuarto producto de prueba con precio alto.',
                'full_description' => 'Producto de prueba con precio mas alto para probar cotizaciones grandes.',
                'price' => 1500.00,
                'cost' => 800.00,
                'iva' => true,
            ],
            [
                'name' => 'Producto de Prueba 5',
                'sku' => 'TEST-005',
                'description' => 'Quinto producto de prueba economico.',
                'full_description' => 'Producto de prueba con precio bajo para probar multiples items en carrito.',
                'price' => 50.00,
                'cost' => 20.00,
                'iva' => true,
            ],
        ];

        foreach ($products as $index => $productData) {
            $productNumber = $index + 1;

            // Create placeholder image (simple SVG)
            $imageName = "test-product-{$productNumber}.svg";
            $imagePath = "products/{$imageName}";
            if (!Storage::disk('public')->exists($imagePath)) {
                $svgContent = $this->generatePlaceholderSvg($productNumber, $productData['name']);
                Storage::disk('public')->put($imagePath, $svgContent);
            }

            // Create placeholder PDF datasheet
            $pdfName = "test-datasheet-{$productNumber}.pdf";
            $pdfPath = "datasheets/{$pdfName}";
            if (!Storage::disk('public')->exists($pdfPath)) {
                $pdfContent = $this->generatePlaceholderPdf($productNumber, $productData['name']);
                Storage::disk('public')->put($pdfPath, $pdfContent);
            }

            \Modules\Product\Models\Product::firstOrCreate(
                ['sku' => $productData['sku']],
                array_merge($productData, [
                    'unit_id' => $unit->id,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'img_path' => $imagePath,
                    'datasheet_path' => $pdfPath,
                    'is_active' => true,
                ])
            );
        }
    }

    /**
     * Generate a simple SVG placeholder image
     */
    private function generatePlaceholderSvg(int $number, string $name): string
    {
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        $color = $colors[($number - 1) % count($colors)];

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">
  <rect width="400" height="400" fill="{$color}"/>
  <rect x="20" y="20" width="360" height="360" fill="white" fill-opacity="0.1" rx="20"/>
  <text x="200" y="180" font-family="Arial, sans-serif" font-size="80" font-weight="bold" fill="white" text-anchor="middle">TEST</text>
  <text x="200" y="260" font-family="Arial, sans-serif" font-size="60" font-weight="bold" fill="white" text-anchor="middle">{$number}</text>
  <text x="200" y="350" font-family="Arial, sans-serif" font-size="14" fill="white" fill-opacity="0.8" text-anchor="middle">Producto de Prueba</text>
</svg>
SVG;
    }

    /**
     * Generate a simple PDF placeholder (minimal valid PDF)
     */
    private function generatePlaceholderPdf(int $number, string $name): string
    {
        // Minimal valid PDF structure
        $content = "Ficha Tecnica - Producto de Prueba {$number}\n\n";
        $content .= "Nombre: {$name}\n";
        $content .= "SKU: TEST-00{$number}\n\n";
        $content .= "Descripcion:\n";
        $content .= "Este es un documento de prueba generado automaticamente.\n";
        $content .= "Puede eliminarse sin afectar el sistema.\n\n";
        $content .= "Especificaciones:\n";
        $content .= "- Material: N/A\n";
        $content .= "- Dimensiones: N/A\n";
        $content .= "- Peso: N/A\n\n";
        $content .= "Fecha de generacion: " . date('Y-m-d H:i:s') . "\n";

        // Simple PDF structure
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";

        $streamContent = "BT\n/F1 12 Tf\n50 700 Td\n";
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $escapedLine = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $line);
            $streamContent .= "({$escapedLine}) Tj\n0 -15 Td\n";
        }
        $streamContent .= "ET";

        $streamLength = strlen($streamContent);
        $pdf .= "4 0 obj\n<< /Length {$streamLength} >>\nstream\n{$streamContent}\nendstream\nendobj\n";
        $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n0\n%%EOF";

        return $pdf;
    }
}
