<?php

namespace Modules\User\Observers;

use Modules\User\Models\User;

class UserObserver
{
    /** Multi-sucursal 2026-09: sin sucursal explicita, el usuario nace en la Matriz. */
    public function creating(User $user): void
    {
        if (empty($user->branch_id)) {
            $user->branch_id = \Modules\Branch\Models\Branch::mainId();
        }
    }

    public function created(User $user): void
    {
        // Si hay un campo 'role' temporal, asignarlo después de la creación
        if (isset($user->_temp_role)) {
            $user->assignRole($user->_temp_role);
            unset($user->_temp_role);
        }
    }

    public function updated(User $user): void
    {
        // Si hay un campo 'role' temporal, asignarlo después de la actualización
        if (isset($user->_temp_role)) {
            $user->syncRoles([$user->_temp_role]);
            unset($user->_temp_role);
        }
    }
}
