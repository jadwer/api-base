<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La migracion 2026_11_07_120000 renombra los roles de sistema que el cliente
 * creo en espanol (cliente, invitado) a sus identificadores (customer, guest),
 * conservando usuarios y permisos; si el destino ya existe, fusiona.
 */
class RenameSpanishSystemRolesMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_11_07_120000_rename_spanish_system_roles_to_identifiers.php';

    /** RefreshDatabase ya la corrio al arrancar: se borra su registro para que vuelva a correr. */
    private function migrate(): void
    {
        DB::table('migrations')->where('migration', 'like', '2026_11_07_120000%')->delete();
        Artisan::call('migrate', ['--path' => self::MIGRATION, '--force' => true]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_renombra_cliente_a_customer_cuando_customer_no_existe(): void
    {
        Role::where('name', 'customer')->delete();
        $cliente = Role::create(['name' => 'cliente', 'guard_name' => 'api']);
        $cliente->givePermissionTo(Permission::findOrCreate('quotes.index', 'api'));
        $user = \App\Models\User::factory()->create();
        $user->assignRole('cliente');

        $this->migrate();

        $this->assertNull(Role::where('name', 'cliente')->first());
        $customer = Role::where('name', 'customer')->first();
        $this->assertNotNull($customer);
        $this->assertSame('Cliente (portal del cliente)', $customer->description);
        $this->assertTrue($user->fresh()->hasRole('customer'));
        $this->assertTrue($customer->hasPermissionTo('quotes.index'));
    }

    public function test_fusiona_en_customer_cuando_ya_existe_y_es_idempotente(): void
    {
        $customer = Role::findOrCreate('customer', 'api');
        $cliente = Role::create(['name' => 'cliente', 'guard_name' => 'api']);
        $cliente->givePermissionTo(Permission::findOrCreate('quotes.show', 'api'));
        $user = \App\Models\User::factory()->create();
        $user->assignRole('cliente');
        $already = \App\Models\User::factory()->create();
        $already->assignRole('customer');

        $this->migrate();
        $this->migrate();

        $this->assertNull(Role::where('name', 'cliente')->first());
        $this->assertSame(1, Role::where('name', 'customer')->count());
        $this->assertTrue($user->fresh()->hasRole('customer'));
        $this->assertTrue($already->fresh()->hasRole('customer'));
        $this->assertTrue($customer->fresh()->hasPermissionTo('quotes.show'));
    }
}
