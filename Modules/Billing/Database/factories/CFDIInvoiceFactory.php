<?php

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Finance\Models\ARInvoice;
use Modules\Contacts\Models\Contact;

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
        $contact = Contact::where('is_customer', true)->first()
            ?? Contact::factory()->customer()->create();

        return [
            'companySettingId' => $companySetting->id,
            'contactId' => $contact->id,
            'arInvoiceId' => null,
            'series' => $companySetting->invoiceSeries ?? 'F',
            'folio' => $this->faker->numberBetween(1, 9999),
            'uuid' => null,
            'tipoComprobante' => 'I', // Ingreso
            'receptorRfc' => strtoupper($this->faker->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}')),
            'receptorNombre' => $this->faker->company(),
            'receptorUsoCfdi' => $this->faker->randomElement(['G01', 'G02', 'G03', 'I01', 'P01']),
            'receptorRegimenFiscal' => $this->faker->randomElement(['601', '603', '605', '606', '612', '621']),
            'receptorDomicilioFiscal' => $this->faker->numerify('#####'),
            'subtotal' => $subtotal,
            'total' => $total,
            'descuento' => 0,
            'iva' => $iva,
            'ieps' => 0,
            'isrRetenido' => 0,
            'ivaRetenido' => 0,
            'moneda' => 'MXN',
            'tipoCambio' => 1.000000,
            'formaPago' => $this->faker->randomElement(['01', '03', '04', '28']),
            'metodoPago' => 'PUE',
            'condicionesPago' => null,
            'cfdiRelacionadoTipo' => null,
            'cfdiRelacionadoUuids' => null,
            'status' => 'draft',
            'fechaEmision' => now(),
            'fechaTimbrado' => null,
            'fechaCancelacion' => null,
            'xmlPath' => null,
            'pdfPath' => null,
            'pacResponse' => null,
            'errorMessage' => null,
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
            'fechaTimbrado' => null,
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
            'fechaTimbrado' => now(),
            'xmlPath' => 'cfdi/xml/' . $this->faker->uuid() . '.xml',
            'pdfPath' => 'cfdi/pdf/' . $this->faker->uuid() . '.pdf',
            'pacResponse' => json_encode([
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
            'fechaTimbrado' => now()->subDays(5),
            'fechaCancelacion' => now(),
        ]);
    }

    /**
     * State: Error during timbrado
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'errorMessage' => 'Error al timbrar: RFC del receptor inválido',
            'pacResponse' => json_encode([
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
            'tipoComprobante' => 'I',
        ]);
    }

    /**
     * State: Tipo Egreso (credit note)
     */
    public function egreso(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipoComprobante' => 'E',
            'cfdiRelacionadoTipo' => '01', // Nota de crédito
            'cfdiRelacionadoUuids' => [$this->faker->uuid()],
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
            'metodoPago' => 'PPD',
            'condicionesPago' => 'Pago en 30 días',
            'formaPago' => '99', // Por definir
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
                'arInvoiceId' => $arInvoice->id,
                'contactId' => $arInvoice->contactId,
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
            'tipoCambio' => $this->faker->randomFloat(6, 16, 20),
        ]);
    }
}
