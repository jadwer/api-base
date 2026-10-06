<?php

namespace Modules\Finance\Support;

/**
 * Paquete B, cierre (2026-09-30): el PATCH de documentos financieros ya era
 * inmutable (ValidatesFinancialImmutability, 87efe13) pero el DELETE seguia
 * abierto: una factura contabilizada o cobrada se podia borrar por el API y
 * dejaba polizas y cobros huerfanos en el GL.
 *
 * Regla: solo se elimina un documento que no tuvo efectos: estado editable,
 * sin poliza contable y sin montos pagados o aplicados. Lo demas se anula con
 * su accion (void, nota de credito), que deja rastro y revierte el GL.
 */
final class FinancialDeletionGuard
{
    /**
     * @param  string[]  $lockedStatuses  estados con efectos (los mismos de la inmutabilidad por PATCH)
     * @param  string[]  $moneyFields  columnas de dinero aplicado/pagado
     */
    public static function reason(object $model, array $lockedStatuses, array $moneyFields = []): ?string
    {
        $status = (string) ($model->status ?? '');
        if (in_array($status, $lockedStatuses, true)) {
            return "Un documento en estado \"{$status}\" no se puede eliminar. Anulalo con su accion para dejar rastro y revertir la contabilidad.";
        }

        if (! empty($model->journal_entry_id)) {
            return 'El documento ya tiene poliza contable y no se puede eliminar. Anulalo con su accion.';
        }

        foreach ($moneyFields as $field) {
            if ((float) ($model->{$field} ?? 0) > 0) {
                return 'El documento ya tiene pagos o aplicaciones registradas y no se puede eliminar.';
            }
        }

        return null;
    }
}
