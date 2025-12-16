<?php

namespace Modules\Billing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Contacts\Models\Contact;
use Carbon\Carbon;

class BillingDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Billing demo data...');

        // Create company settings
        $companySetting = $this->createCompanySetting();
        $this->command->info("Created company setting: {$companySetting->company_name}");

        // Create sample CFDI invoices if contacts exist
        $contact = Contact::where('is_customer', true)->first();
        if ($contact) {
            $invoiceCount = $this->createSampleInvoices($companySetting, $contact);
            $this->command->info("Created {$invoiceCount} sample CFDI invoices");
        } else {
            $this->command->warn('No customer contacts found. Skipping CFDI invoice creation.');
        }
    }

    private function createCompanySetting(): CompanySetting
    {
        // Check if a company setting already exists
        $existing = CompanySetting::where('is_active', true)->first();
        if ($existing) {
            return $existing;
        }

        return CompanySetting::create([
            'company_name' => 'Atomo Soluciones SA de CV',
            'rfc' => 'ATS090101001',
            'tax_regime' => '601', // General de Ley Personas Morales
            'postal_code' => '06600',
            'invoice_series' => 'F',
            'credit_note_series' => 'N',
            'next_invoice_folio' => 1,
            'next_credit_note_folio' => 1,
            'pac_provider' => 'SW Sapien',
            'pac_username' => null, // Will be configured in production
            'pac_password' => null,
            'pac_production_mode' => false,
            'certificate_file' => null,
            'key_file' => null,
            'key_password' => null,
            'logo_path' => null,
            'additional_settings' => [
                'email' => 'facturacion@atomosoluciones.com',
                'phone' => '55 1234 5678',
                'website' => 'www.atomosoluciones.com',
                'address' => 'Av. Paseo de la Reforma 250, Col. Juárez, CDMX',
            ],
            'is_active' => true,
        ]);
    }

    private function createSampleInvoices(CompanySetting $companySetting, Contact $contact): int
    {
        $count = 0;

        // Sample invoice data with different statuses
        $invoices = [
            [
                'receptor_nombre' => 'Grupo Industrial MX SA de CV',
                'receptor_rfc' => 'GIM850101001',
                'subtotal' => 2500000, // $25,000 MXN in centavos
                'status' => 'draft',
                'uso_cfdi' => 'G03', // Gastos en general
            ],
            [
                'receptor_nombre' => 'Tech Solutions de México SA de CV',
                'receptor_rfc' => 'TSM900315001',
                'subtotal' => 1800000, // $18,000 MXN
                'status' => 'valid',
                'uso_cfdi' => 'G01', // Adquisición de mercancías
            ],
            [
                'receptor_nombre' => 'Comercializadora del Norte SA de CV',
                'receptor_rfc' => 'CNO880520001',
                'subtotal' => 4500000, // $45,000 MXN
                'status' => 'valid',
                'uso_cfdi' => 'I01', // Construcciones
            ],
            [
                'receptor_nombre' => 'Servicios Integrales MX SC',
                'receptor_rfc' => 'SIM950610001',
                'subtotal' => 850000, // $8,500 MXN
                'status' => 'draft',
                'uso_cfdi' => 'P01', // Por definir
            ],
            [
                'receptor_nombre' => 'Consultores Asociados SC',
                'receptor_rfc' => 'CAS870325001',
                'subtotal' => 1200000, // $12,000 MXN
                'status' => 'cancelled',
                'uso_cfdi' => 'G03',
            ],
        ];

        $folio = $companySetting->next_invoice_folio;

        foreach ($invoices as $invoiceData) {
            $subtotal = $invoiceData['subtotal'];
            $iva = (int)($subtotal * 0.16);
            $total = $subtotal + $iva;

            $invoice = CFDIInvoice::create([
                'company_setting_id' => $companySetting->id,
                'contact_id' => $contact->id,
                'ar_invoice_id' => null,
                'series' => $companySetting->invoice_series,
                'folio' => $folio++,
                'uuid' => $invoiceData['status'] === 'valid' ? $this->generateUUID() : null,
                'tipo_comprobante' => 'I',
                'receptor_rfc' => $invoiceData['receptor_rfc'],
                'receptor_nombre' => $invoiceData['receptor_nombre'],
                'receptor_uso_cfdi' => $invoiceData['uso_cfdi'],
                'receptor_regimen_fiscal' => '601',
                'receptor_domicilio_fiscal' => '06600',
                'subtotal' => $subtotal,
                'total' => $total,
                'descuento' => 0,
                'iva' => $iva,
                'ieps' => 0,
                'isr_retenido' => 0,
                'iva_retenido' => 0,
                'moneda' => 'MXN',
                'tipo_cambio' => 1.000000,
                'forma_pago' => '03', // Transferencia electrónica
                'metodo_pago' => 'PUE', // Pago en una sola exhibición
                'condiciones_pago' => null,
                'cfdi_relacionado_tipo' => null,
                'cfdi_relacionado_uuids' => null,
                'status' => $invoiceData['status'],
                'fecha_emision' => $invoiceData['status'] === 'draft'
                    ? Carbon::now()
                    : Carbon::now()->subDays(rand(1, 30)),
                'fecha_timbrado' => $invoiceData['status'] === 'valid'
                    ? Carbon::now()->subDays(rand(1, 30))
                    : null,
                'fecha_cancelacion' => $invoiceData['status'] === 'cancelled'
                    ? Carbon::now()->subDays(rand(1, 5))
                    : null,
                'xml_path' => $invoiceData['status'] === 'valid' ? 'cfdi/xml/demo_' . $folio . '.xml' : null,
                'pdf_path' => $invoiceData['status'] === 'valid' ? 'cfdi/pdf/demo_' . $folio . '.pdf' : null,
                'pac_response' => $invoiceData['status'] === 'valid'
                    ? json_encode(['success' => true, 'message' => 'CFDI timbrado correctamente'])
                    : null,
                'error_message' => null,
                'metadata' => [
                    'created_by' => 'demo_seeder',
                    'demo_invoice' => true,
                ],
            ]);

            $count++;
        }

        // Update next folio
        $companySetting->update(['next_invoice_folio' => $folio]);

        return $count;
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
