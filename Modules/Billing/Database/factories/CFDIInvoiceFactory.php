<?php

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Finance\Models\ARInvoice;
use Modules\Sales\Models\Contact;

class CFDIInvoiceFactory extends Factory
{
    protected $model = CFDIInvoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(100000, 1000000); // In cents
        $iva = (int) ($subtotal * 0.16); // 16% IVA
        $total = $subtotal + $iva;

        // Get or create dependencies
        $companySetting = CompanySetting::first() ?? CompanySetting::factory()->create();
        $contact = Contact::where('contact_type', 'customer')->first()
            ?? Contact::factory()->customer()->create();

        return [
            'company_setting_id' => $companySetting->id,
            'contact_id' => $contact->id,
            'ar_invoice_id' => null,
            'series' => $companySetting->invoice_series ?? 'F',
            'folio' => $this->faker->numberBetween(1, 9999),
            'uuid' => null,
            'tipo_comprobante' => 'I', // Ingreso
            'receptor_rfc' => strtoupper($this->faker->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}')),
            'receptor_nombre' => $this->faker->company(),
            'receptor_uso_cfdi' => $this->faker->randomElement(['G01', 'G02', 'G03', 'I01', 'P01']),
            'receptor_regimen_fiscal' => $this->faker->randomElement(['601', '603', '605', '606', '612', '621']),
            'receptor_domicilio_fiscal' => $this->faker->numerify('#####'),
            'subtotal' => $subtotal,
            'total' => $total,
            'descuento' => 0,
            'iva' => $iva,
            'ieps' => 0,
            'isr_retenido' => 0,
            'iva_retenido' => 0,
            'moneda' => 'MXN',
            'tipo_cambio' => 1.000000,
            'forma_pago' => $this->faker->randomElement(['01', '03', '04', '28']),
            'metodo_pago' => 'PUE',
            'condiciones_pago' => null,
            'cfdi_relacionado_tipo' => null,
            'cfdi_relacionado_uuids' => null,
            'status' => 'draft',
            'fecha_emision' => now(),
            'fecha_timbrado' => null,
            'fecha_cancelacion' => null,
            'xml_path' => null,
            'pdf_path' => null,
            'pac_response' => null,
            'error_message' => null,
            'metadata' => [],
        ];
    }

    /**
     * State: Draft invoice
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'uuid' => null,
            'fecha_timbrado' => null,
        ]);
    }

    /**
     * State: Valid (timbrado) invoice
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'valid',
            'uuid' => $this->faker->uuid(),
            'fecha_timbrado' => now(),
            'xml_path' => 'cfdi/xml/' . $this->faker->uuid() . '.xml',
            'pdf_path' => 'cfdi/pdf/' . $this->faker->uuid() . '.pdf',
            'pac_response' => json_encode([
                'success' => true,
                'message' => 'CFDI timbrado correctamente',
            ]),
        ]);
    }

    /**
     * State: Cancelled invoice
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'uuid' => $this->faker->uuid(),
            'fecha_timbrado' => now()->subDays(5),
            'fecha_cancelacion' => now(),
        ]);
    }

    /**
     * State: Error during timbrado
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'error_message' => 'Error al timbrar: RFC del receptor inválido',
            'pac_response' => json_encode([
                'success' => false,
                'error' => 'RFC_INVALIDO',
            ]),
        ]);
    }

    /**
     * State: Tipo Ingreso (default)
     */
    public function ingreso(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_comprobante' => 'I',
        ]);
    }

    /**
     * State: Tipo Egreso (credit note)
     */
    public function egreso(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_comprobante' => 'E',
            'cfdi_relacionado_tipo' => '01', // Nota de crédito
            'cfdi_relacionado_uuids' => [$this->faker->uuid()],
        ]);
    }

    /**
     * State: With discount
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $descuento = (int) ($subtotal * 0.10); // 10% discount
            $subtotalConDescuento = $subtotal - $descuento;
            $iva = (int) ($subtotalConDescuento * 0.16);
            $total = $subtotalConDescuento + $iva;

            return [
                'descuento' => $descuento,
                'iva' => $iva,
                'total' => $total,
            ];
        });
    }

    /**
     * State: Payment in installments (PPD)
     */
    public function ppd(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo_pago' => 'PPD',
            'condiciones_pago' => 'Pago en 30 días',
            'forma_pago' => '99', // Por definir
        ]);
    }

    /**
     * State: With AR Invoice relation
     */
    public function withARInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $arInvoice = ARInvoice::first() ?? ARInvoice::factory()->create();
            return [
                'ar_invoice_id' => $arInvoice->id,
                'contact_id' => $arInvoice->contact_id,
            ];
        });
    }

    /**
     * State: USD Currency
     */
    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'moneda' => 'USD',
            'tipo_cambio' => $this->faker->randomFloat(6, 16, 20),
        ]);
    }
}
