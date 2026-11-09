<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** La migracion 2026_11_09_120000 quita al rol del portal las acciones de CFDI que son del personal. */
class RevokeStaffCfdiActionsFromCustomerMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quita_al_portal_las_acciones_de_personal_y_le_deja_ver_y_descargar(): void
    {
        $staffOnly = ['billing.cfdi-invoices.generate-pdf', 'billing.cfdi-invoices.generate-xml', 'billing.cfdi-invoices.prefactura', 'billing.cfdi-invoices.validate', 'billing.cfdi-invoices.cancellation-status'];
        $portal = ['billing.cfdi-invoices.index', 'billing.cfdi-invoices.show', 'billing.cfdi-invoices.download-pdf', 'billing.cfdi-invoices.download-xml', 'billing.cfdi-invoices.preview-pdf'];
        $customer = Role::findOrCreate('customer', 'api');
        $cliente = Role::findOrCreate('cliente', 'api');
        $admin = Role::findOrCreate('admin', 'api');
        foreach ([...$staffOnly, ...$portal] as $name) {
            $permission = Permission::findOrCreate($name, 'api');
            $customer->givePermissionTo($permission);
            $cliente->givePermissionTo($permission);
            $admin->givePermissionTo($permission);
        }
        $user = $this->getCustomerUser();
        $user->givePermissionTo(Permission::findOrCreate('billing.cfdi-invoices.validate', 'api'));

        DB::table('migrations')->where('migration', 'like', '2026_11_09_120000%')->delete();
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_11_09_120000_revoke_staff_cfdi_actions_from_customer_role.php', '--force' => true]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([$customer, $cliente] as $role) {
            $names = $role->fresh()->permissions->pluck('name')->all();
            $this->assertSame([], array_values(array_intersect($names, $staffOnly)), "{$role->name} conserva acciones de personal");
            $this->assertSame([], array_values(array_diff($portal, $names)), "{$role->name} perdio permisos del portal");
        }
        $this->assertFalse($user->fresh()->hasDirectPermission('billing.cfdi-invoices.validate'), 'El permiso directo del usuario del portal debe revocarse');
        $this->assertTrue($admin->fresh()->hasPermissionTo('billing.cfdi-invoices.generate-pdf'));
    }
}
