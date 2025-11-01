<?php

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Product\Models\Product;

class CFDIItemFactory extends Factory
{
    protected $model = CFDIItem::class;

    public function definition(): array
    {
        $cantidad = $this->faker->randomFloat(2, 1, 100);
        $valorUnitario = $this->faker->numberBetween(10000, 500000); // In cents
        $importe = (int) ($cantidad * $valorUnitario);

        // Get or create dependencies
        $invoice = CFDIInvoice::first() ?? CFDIInvoice::factory()->create();
        $product = Product::first();

        return [
            'cfdi_invoice_id' => $invoice->id,
            'product_id' => $product ? $product->id : null,
            'numero_linea' => 1,
            'clave_prod_serv' => $this->faker->randomElement(['01010101', '10101500', '43231500', '50202200', '78101800']),
            'clave_unidad' => $this->faker->randomElement(['ACT', 'E48', 'H87', 'KGM', 'LTR']),
            'unidad' => $this->faker->randomElement(['Actividad', 'Servicio', 'Pieza', 'Kilogramo', 'Litro']),
            'cantidad' => $cantidad,
            'descripcion' => $this->faker->sentence(6),
            'no_identificacion' => $this->faker->optional()->bothify('SKU-####'),
            'valor_unitario' => $valorUnitario,
            'importe' => $importe,
            'descuento' => 0,
            'impuestos' => $this->generateImpuestos($importe),
            'objeto_imp' => '02', // Sí objeto de impuesto
            'numero_pedimento' => null,
            'cuenta_predial' => null,
            'informacion_aduanera' => null,
            'metadata' => [],
        ];
    }

    /**
     * Generate tax structure (IVA 16%)
     */
    protected function generateImpuestos(int $importe): array
    {
        $iva = (int) ($importe * 0.16);

        return [
            'traslados' => [
                [
                    'base' => $importe,
                    'impuesto' => '002', // IVA
                    'tipo_factor' => 'Tasa',
                    'tasa_o_cuota' => '0.160000',
                    'importe' => $iva,
                ]
            ],
            'retenciones' => []
        ];
    }

    /**
     * State: With discount
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $descuento = (int) ($attributes['importe'] * 0.10); // 10% discount
            return [
                'descuento' => $descuento,
            ];
        });
    }

    /**
     * State: With taxes (IVA 16% + IEPS 8%)
     */
    public function withTaxes(): static
    {
        return $this->state(function (array $attributes) {
            $importe = $attributes['importe'];
            $iva = (int) ($importe * 0.16);
            $ieps = (int) ($importe * 0.08);

            return [
                'objeto_imp' => '02', // Sí objeto de impuesto
                'impuestos' => [
                    'traslados' => [
                        [
                            'base' => $importe,
                            'impuesto' => '002', // IVA
                            'tipo_factor' => 'Tasa',
                            'tasa_o_cuota' => '0.160000',
                            'importe' => $iva,
                        ],
                        [
                            'base' => $importe,
                            'impuesto' => '003', // IEPS
                            'tipo_factor' => 'Tasa',
                            'tasa_o_cuota' => '0.080000',
                            'importe' => $ieps,
                        ]
                    ],
                    'retenciones' => []
                ],
            ];
        });
    }

    /**
     * State: No taxes (exento)
     */
    public function exento(): static
    {
        return $this->state(fn (array $attributes) => [
            'objeto_imp' => '04', // Sí objeto pero exento
            'impuestos' => [
                'traslados' => [],
                'retenciones' => []
            ],
        ]);
    }

    /**
     * State: Not object of tax
     */
    public function notObjectOfTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'objeto_imp' => '01', // No objeto de impuesto
            'impuestos' => null,
        ]);
    }

    /**
     * State: With product reference
     */
    public function withProduct(): static
    {
        return $this->state(function (array $attributes) {
            $product = Product::first() ?? Product::factory()->create();
            return [
                'product_id' => $product->id,
                'no_identificacion' => $product->sku,
                'descripcion' => $product->name,
            ];
        });
    }

    /**
     * State: Service (not a product)
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => null,
            'clave_prod_serv' => '80101500', // Servicios profesionales
            'clave_unidad' => 'E48', // Servicio
            'unidad' => 'Servicio',
            'descripcion' => 'Servicios profesionales de consultoría',
        ]);
    }

    /**
     * State: With customs information
     */
    public function withCustoms(): static
    {
        return $this->state(fn (array $attributes) => [
            'numero_pedimento' => $this->faker->numerify('##  ##  ####  #######'),
            'informacion_aduanera' => [
                [
                    'numero_pedimento' => $this->faker->numerify('##  ##  ####  #######'),
                ]
            ],
        ]);
    }

    /**
     * State: Line number
     */
    public function lineNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'numero_linea' => $number,
        ]);
    }

    /**
     * State: For specific invoice
     */
    public function forInvoice(CFDIInvoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'cfdi_invoice_id' => $invoice->id,
        ]);
    }
}
