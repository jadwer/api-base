<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\PaymentMethod;
use Illuminate\Support\Facades\Log;

/**
 * PaymentMethodsSeeder
 *
 * Crea los métodos de pago estándar para México
 */
class PaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'CASH',
                'name' => 'Efectivo',
                'type' => 'cash',
                'requires_reference' => false,
                'is_active' => true,
            ],
            [
                'code' => 'TRANSFER',
                'name' => 'Transferencia Bancaria',
                'type' => 'electronic',
                'requires_reference' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CARD',
                'name' => 'Tarjeta de Crédito/Débito',
                'type' => 'card',
                'requires_reference' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CHECK',
                'name' => 'Cheque',
                'type' => 'check',
                'requires_reference' => true,
                'is_active' => true,
            ],
            [
                'code' => 'DEPOSIT',
                'name' => 'Depósito Bancario',
                'type' => 'electronic',
                'requires_reference' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SPEI',
                'name' => 'SPEI (Sistema de Pagos Electrónicos Interbancarios)',
                'type' => 'electronic',
                'requires_reference' => true,
                'is_active' => true,
            ],
            [
                'code' => 'PAYPAL',
                'name' => 'PayPal',
                'type' => 'digital',
                'requires_reference' => true,
                'is_active' => true,
            ],
            [
                'code' => 'MERCADOPAGO',
                'name' => 'Mercado Pago',
                'type' => 'digital',
                'requires_reference' => true,
                'is_active' => true,
            ],
        ];

        foreach ($methods as $methodData) {
            PaymentMethod::firstOrCreate(
                ['code' => $methodData['code']],
                $methodData
            );
        }

        Log::info('Payment Methods seeded', [
            'count' => PaymentMethod::count(),
        ]);
    }
}
