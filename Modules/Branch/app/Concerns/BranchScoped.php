<?php

namespace Modules\Branch\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Branch\Models\Branch;

/**
 * Restriccion real por sucursal (decision de Gabino 2026-09-24).
 *
 * Un usuario autenticado que NO es god/admin/customer solo ve y abre los
 * documentos de sus sucursales (principal + con acceso). Sin usuario (jobs,
 * webhooks, seeders, consola) no se restringe. customer queda fuera porque el
 * portal del cliente ya se filtra por su contacto.
 *
 * Crear o mover un documento a una sucursal fuera de su acceso se rechaza con
 * 422. El default de sucursal (Branch::defaultId) es la principal del
 * usuario, que siempre esta en su acceso.
 */
trait BranchScoped
{
    public static function bootBranchScoped(): void
    {
        static::addGlobalScope('branch_access', function (Builder $query) {
            $ids = Branch::restrictedIdsForCurrentUser();
            if ($ids !== null) {
                $query->whereIn($query->getModel()->getTable() . '.branch_id', $ids);
            }
        });

        static::saving(function ($model) {
            if (empty($model->branch_id) || ! $model->isDirty('branch_id')) {
                return;
            }
            $ids = Branch::restrictedIdsForCurrentUser();
            if ($ids !== null && ! in_array((int) $model->branch_id, $ids, true)) {
                throw ValidationException::withMessages([
                    'branchId' => 'No tienes acceso a la sucursal seleccionada.',
                ]);
            }
        });
    }
}
