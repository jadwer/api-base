<?php

namespace Modules\User\Observers;

use Modules\User\Models\User;

class UserObserver
{
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
