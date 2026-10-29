<?php

namespace Modules\Finance\Support;

/**
 * Nombre en espanol del estado de un documento financiero para los mensajes
 * que ve el usuario (regla 9): antes salia el valor interno ("draft", "posted").
 */
final class FinancialStatusLabel
{
    private const LABELS = [
        'draft' => 'Borrador',
        'pending' => 'Pendiente',
        'approved' => 'Aprobado',
        'posted' => 'Contabilizado',
        'partial' => 'Pago parcial',
        'paid' => 'Pagado',
        'overdue' => 'Vencido',
        'void' => 'Anulado',
        'voided' => 'Anulado',
        'cancelled' => 'Cancelado',
        'completed' => 'Completado',
        'failed' => 'Fallido',
        'refunded' => 'Reembolsado',
        'reversed' => 'Revertido',
    ];

    public static function of(?string $status): string
    {
        $status = (string) $status;

        return self::LABELS[$status] ?? $status;
    }
}
