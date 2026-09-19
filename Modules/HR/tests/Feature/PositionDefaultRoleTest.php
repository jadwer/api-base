<?php

namespace Modules\HR\Tests\Feature;

use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Position;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * "Se liga la actividad con los permisos" (Jasim, 18-sep-2026): el puesto
 * trae un rol por defecto que recibe el usuario ligado si no tiene ninguno.
 */
class PositionDefaultRoleTest extends TestCase
{
    private function position(?string $defaultRole): Position
    {
        $department = Department::factory()->create();

        return Position::create([
            'department_id' => $department->id,
            'title' => 'Ejecutivo de Ventas',
            'level' => 'mid',
            'is_active' => true,
            'default_role' => $defaultRole,
        ]);
    }

    private function employeeFor(User $user, Position $position): Employee
    {
        return Employee::create([
            'department_id' => $position->department_id,
            'position_id' => $position->id,
            'user_id' => $user->id,
            'employee_code' => 'EMP-' . $user->id,
            'first_name' => 'Ana',
            'last_name' => 'Ventas',
            'email' => $user->email,
            'hire_date' => now()->toDateString(),
            'salary' => 0,
            'status' => 'active',
        ]);
    }

    public function test_user_without_roles_receives_the_positions_default_role(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->roles()->exists());

        $this->employeeFor($user, $this->position('tech'));

        $this->assertTrue($user->fresh()->hasRole('tech'));
    }

    public function test_existing_roles_are_never_overridden(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->employeeFor($user, $this->position('tech'));

        $roles = $user->fresh()->getRoleNames()->all();
        $this->assertSame(['admin'], $roles);
    }

    public function test_position_without_default_role_or_unknown_role_does_nothing(): void
    {
        $user = User::factory()->create();
        $this->employeeFor($user, $this->position(null));
        $this->assertFalse($user->fresh()->roles()->exists());

        $other = User::factory()->create();
        $this->employeeFor($other, $this->position('rol-que-no-existe'));
        $this->assertFalse($other->fresh()->roles()->exists());
    }

    public function test_default_role_is_exposed_and_validated_in_the_api(): void
    {
        $admin = $this->getAdminUser();
        $position = $this->position(null);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData([
                'type' => 'positions',
                'id' => (string) $position->id,
                'attributes' => ['defaultRole' => 'tech'],
            ])
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertFetchedOne($position);
        $this->assertSame('tech', $position->fresh()->default_role);

        $invalid = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData([
                'type' => 'positions',
                'id' => (string) $position->id,
                'attributes' => ['defaultRole' => 'no-existe'],
            ])
            ->patch("/api/v1/positions/{$position->id}");

        $invalid->assertStatus(422);
    }
}
