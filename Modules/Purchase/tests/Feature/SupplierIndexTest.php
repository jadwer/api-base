<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPermissions(string $roleName = 'test_role', array $permissions = []): User
    {
        $user = User::factory()->create();
        
        $role = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => $roleName . '_' . uniqid(),
            'guard_name' => 'api'
        ]);
        
        foreach ($permissions as $permissionName) {
            $permission = \Modules\PermissionManager\Models\Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'api'
            ]);
            $role->givePermissionTo($permission);
        }
        
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_list_suppliers(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['suppliers.index']);

        // Create test data
        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('suppliers')
            ->get('/api/v1/suppliers');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'phone',
                        'address',
                        'rfc',
                        'isActive',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
            'jsonapi',
        ]);
        // El seeder crea 5 + nosotros creamos 3 = al menos 8 total
        $this->assertGreaterThanOrEqual(8, count($response->json('data')));
    }

    public function test_admin_can_filter_suppliers_by_active_status(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['suppliers.index']);

        // Create test data
        Supplier::factory()->count(2)->create(['is_active' => true]);
        Supplier::factory()->count(1)->create(['is_active' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('suppliers')
            ->get('/api/v1/suppliers?filter[isActive]=true');

        $response->assertOk();
        // Verificamos que hay al menos nuestros 2 activos + los del seeder (que por defecto son activos)
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
        
        // Verificamos que todos los retornados son activos
        $activeStates = collect($response->json('data'))->pluck('attributes.isActive');
        $this->assertTrue($activeStates->every(fn($state) => $state === true));
    }

    public function test_admin_can_sort_suppliers_by_name(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['suppliers.index']);

        // Create test data with nombres específicos para verificar ordenamiento
        $supplierZ = Supplier::factory()->create(['name' => 'Z Test Supplier']);
        $supplierA = Supplier::factory()->create(['name' => 'A Test Supplier']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('suppliers')
            ->get('/api/v1/suppliers?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        
        // Verificar que nuestros suppliers específicos están en el orden correcto
        $aIndex = $names->search('A Test Supplier');
        $zIndex = $names->search('Z Test Supplier');
        
        $this->assertTrue($aIndex !== false && $zIndex !== false, 'Suppliers de prueba no encontrados');
        $this->assertLessThan($zIndex, $aIndex, 'El ordenamiento ascendente no es correcto');
    }

    public function test_admin_can_search_suppliers_by_name(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['suppliers.index']);

        // Create test data
        Supplier::factory()->create(['name' => 'ACME Corporation']);
        Supplier::factory()->create(['name' => 'XYZ Limited']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('suppliers')
            ->get('/api/v1/suppliers?filter[name]=ACME Corporation');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
        
        // Verificar que encontramos nuestro supplier
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertTrue($names->contains('ACME Corporation'));
    }

    public function test_unauthorized_user_cannot_list_suppliers(): void
    {
        $response = $this->jsonApi()
            ->expects('suppliers')
            ->get('/api/v1/suppliers');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_suppliers(): void
    {
        $user = $this->createUserWithPermissions('user', []); // Sin permisos

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('suppliers')
            ->get('/api/v1/suppliers');
        $response->assertStatus(403);
    }
}
