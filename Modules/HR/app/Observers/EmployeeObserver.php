<?php

namespace Modules\HR\Observers;

use Modules\HR\Models\Employee;
use Modules\PermissionManager\Models\Role;

/**
 * Puesto -> rol por defecto (multi-sucursal y puesto, sprint 1, 2026-09).
 *
 * Cuando un empleado queda ligado a un usuario y su puesto declara
 * default_role, el usuario recibe ese rol SOLO si todavia no tiene ninguno:
 * nunca pisa una asignacion explicita hecha desde el editor de usuarios.
 */
class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        $this->applyDefaultRole($employee);
    }

    public function updated(Employee $employee): void
    {
        if ($employee->wasChanged(['user_id', 'position_id'])) {
            $this->applyDefaultRole($employee);
        }
    }

    private function applyDefaultRole(Employee $employee): void
    {
        if (! $employee->user_id || ! $employee->position_id) {
            return;
        }

        $role = $employee->position?->default_role;
        if (! $role || ! Role::where('name', $role)->where('guard_name', 'api')->exists()) {
            return;
        }

        $user = $employee->user;
        if (! $user || $user->roles()->exists()) {
            return;
        }

        $user->assignRole($role);
    }
}
