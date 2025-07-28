<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Sales\Models\Customer;
use Modules\Sales\Models\SalesOrder;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'customers.destroy', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'customers.view', 'guard_name' => 'api']);
        
        // Crear roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'tech', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
    }

    private function createUserWithPermissions(string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        $roleModel = Role::findByName($role, 'api');
        
        if (!empty($permissions)) {
            $roleModel->givePermissionTo($permissions);
        }
        
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_delete_customer_without_orders()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);
        
        $customer = Customer::factory()->create([
            'name' => 'Cliente a Eliminar',
            'email' => 'eliminar@test.com'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        // Verificar que el customer fue eliminado de la base de datos
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_cannot_delete_customer_with_sales_orders()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);
        
        $customer = Customer::factory()->create();
        
        // Crear una orden de venta para este customer
        SalesOrder::factory()->create([
            'customer_id' => $customer->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(422);
        
        // Verificar que el customer aún existe en la base de datos
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id
        ]);
        
        // Verificar mensaje de error
        $response->assertJsonFragment([
            'detail' => 'Cannot delete customer with existing sales orders'
        ]);
    }

    public function test_can_delete_customer_after_removing_all_orders()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);
        
        $customer = Customer::factory()->create();
        
        // Crear y luego eliminar una orden
        $order = SalesOrder::factory()->create(['customer_id' => $customer->id]);
        $order->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_can_delete_inactive_customer()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);
        
        $customer = Customer::factory()->create([
            'is_active' => false,
            'name' => 'Cliente Inactivo'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_tech_user_can_delete_customer_with_permission()
    {
        $tech = $this->createUserWithPermissions('tech', ['customers.destroy']);
        
        $customer = Customer::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_user_without_permission_cannot_delete_customer()
    {
        $user = $this->createUserWithPermissions('customer', []);
        
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(403);
        
        // Verificar que el customer aún existe
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_guest_cannot_delete_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(401);
        
        // Verificar que el customer aún existe
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_cannot_delete_nonexistent_customer()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete('/api/v1/customers/999999');

        $response->assertStatus(404);
    }

    public function test_delete_customer_removes_related_activity_logs()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);
        
        $customer = Customer::factory()->create();
        
        // Simular actividad del customer (el modelo usa LogsActivity)
        $customer->update(['name' => 'Nombre Actualizado']);

        // Verificar que hay logs de actividad
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Customer::class,
            'subject_id' => $customer->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        // Verificar que el customer fue eliminado
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
        
        // Verificar que los logs de actividad también fueron eliminados
        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Customer::class,
            'subject_id' => $customer->id
        ]);
    }

    public function test_delete_response_is_empty()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);
        
        $customer = Customer::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        $response->assertNoContent();
    }

    public function test_delete_customer_with_high_credit_limit()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);
        
        $customer = Customer::factory()->create([
            'credit_limit' => 100000.00,
            'current_credit' => 50000.00
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }

    public function test_delete_customer_removes_metadata()
    {
        $admin = $this->createUserWithPermissions('admin', ['customers.destroy']);
        
        $customer = Customer::factory()->create([
            'metadata' => [
                'source' => 'web',
                'preferences' => ['payment_method' => 'credit'],
                'notes' => 'Cliente importante'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('customers')
            ->delete("/api/v1/customers/{$customer->id}");

        $response->assertStatus(204);
        
        // Verificar que todo el registro fue eliminado
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id
        ]);
    }
}
