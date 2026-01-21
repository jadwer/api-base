<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
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
 * - Folio sequences
 * - Invoice series
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
        $this->seedDefaultWarehouse();
        // Note: FolioSequences are created by migration 2026_01_18_200000_create_folio_sequences_table.php
        $this->seedDefaultCompanySetting();
        $this->seedInvoiceSeries();

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
            ['rfc' => 'XAXX010101000'], // RFC generico para pruebas
            [
                'company_name' => 'Mi Empresa SA de CV',
                'tax_regime' => '601', // General de Ley Personas Morales
                'postal_code' => '00000',
                'invoice_series' => 'FAC',
                'credit_note_series' => 'N',
                'next_invoice_folio' => 1,
                'next_credit_note_folio' => 1,
                'pac_production_mode' => false,
                'is_active' => true,
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
}
