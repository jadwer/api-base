<?php

namespace Modules\Accounting\Support;

use Modules\Accounting\Models\Account;

/**
 * Cuentas contables que usa el ciclo de ventas y compras (auditoria finanzas
 * 2026-10, puntos 1 y 2; reglas alineadas al PLAN_FINANZAS de AtomoPlatform):
 *
 * - Venta: Dr Clientes / Cr Ventas (subtotal) / Cr IVA trasladado no cobrado.
 * - Cobro: Dr Bancos / Cr Clientes y reclasifica la parte cobrada del IVA de
 *   "no cobrado" a IVA por pagar.
 * - Recepcion de compra: Dr Almacen / Cr Mercancia recibida por facturar.
 * - Factura de proveedor: Dr Mercancia recibida por facturar (o gasto si no
 *   viene de una orden) / Dr IVA acreditable pendiente / Cr Proveedores.
 *
 * Las tres cuentas nuevas (1110, 2109, 2110) se siembran por migracion; si un
 * catalogo no las trae (prueba, tenant nuevo), se crean bajo su cuenta padre.
 */
final class GlAccounts
{
    public const BANK = '1102';
    public const CUSTOMERS = '1104';
    public const VAT_INPUT = '1107';              // IVA acreditable (pagado)
    public const INVENTORY = '1108';
    public const VAT_INPUT_PENDING = '1110';      // IVA acreditable pendiente de pago
    public const SUPPLIERS = '2101';
    public const VAT_OUTPUT = '2104';             // IVA por pagar (cobrado)
    public const VAT_OUTPUT_PENDING = '2109';     // IVA trasladado no cobrado
    public const GOODS_RECEIVED = '2110';         // Mercancia recibida por facturar
    public const REVENUE = '4101';
    public const COST_OF_SALES = '5101';

    /**
     * Cuentas del sistema que se crean si faltan: codigo => [nombre, tipo, padre].
     * `nature` queda en su default como el resto del catalogo (ninguna logica lo lee).
     */
    public const SYSTEM = [
        self::VAT_INPUT_PENDING => ['IVA Acreditable Pendiente de Pago', 'asset', '1100'],
        self::VAT_OUTPUT_PENDING => ['IVA Trasladado No Cobrado', 'liability', '2100'],
        self::GOODS_RECEIVED => ['Mercancía Recibida por Facturar', 'liability', '2100'],
    ];

    private const LABELS = [
        self::BANK => 'bancos',
        self::CUSTOMERS => 'clientes',
        self::VAT_INPUT => 'IVA acreditable',
        self::SUPPLIERS => 'proveedores',
        self::VAT_OUTPUT => 'IVA por pagar',
        self::REVENUE => 'ventas',
        self::COST_OF_SALES => 'costo de ventas',
    ];

    /**
     * Cuenta afectable por codigo. Las del sistema se crean si faltan; las
     * demas son del catalogo y su ausencia es un error de configuracion.
     */
    public static function get(string $code): Account
    {
        $account = Account::where('code', $code)->where('is_postable', true)->first();
        if ($account) {
            return $account;
        }

        if (isset(self::SYSTEM[$code])) {
            [$name, $type, $parentCode] = self::SYSTEM[$code];
            $parent = Account::where('code', $parentCode)->first();

            return Account::create([
                'code' => $code,
                'name' => $name,
                'account_type' => $type,
                'level' => $parent ? ((int) $parent->level + 1) : 3,
                'parent_id' => $parent?->id,
                'company_id' => $parent?->company_id,
                'is_postable' => true,
                'status' => 'active',
            ]);
        }

        $label = self::LABELS[$code] ?? $code;
        throw new \Exception("No existe la cuenta contable de {$label} ({$code}) o no es afectable. Configura el catálogo de cuentas.");
    }

    /**
     * IVA de la parte cobrada (o pagada) entre dos saldos acumulados. Telescopico:
     * la suma de todos los abonos da exactamente el IVA de la factura al liquidar.
     */
    public static function taxPortion(float $tax, float $total, float $paidBefore, float $paidAfter): float
    {
        if ($tax <= 0 || $total <= 0) {
            return 0.0;
        }
        $before = round($tax * min($paidBefore, $total) / $total, 2);
        $after = round($tax * min($paidAfter, $total) / $total, 2);

        return round($after - $before, 2);
    }
}
