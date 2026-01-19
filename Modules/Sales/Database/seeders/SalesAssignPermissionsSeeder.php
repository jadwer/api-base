<?php

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SalesAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asignar permisos a roles
        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        $techRole = Role::where('name', 'tech')->where('guard_name', 'api')->first();

        if ($godRole) {
            // God tiene todos los permisos
            $allPermissions = Permission::where('guard_name', 'api')
                                      ->where(function($query) {
                                          $query->where('name', 'like', 'customers.%')
                                                ->orWhere('name', 'like', 'sales-orders.%')
                                                ->orWhere('name', 'like', 'sales-order-items.%')
                                                ->orWhere('name', 'like', 'shipments.%')
                                                ->orWhere('name', 'like', 'shipment-items.%')
                                                ->orWhere('name', 'like', 'backorders.%')
                                                ->orWhere('name', 'like', 'discount-rules.%')
                                                ->orWhere('name', 'like', 'quotes.%')
                                                ->orWhere('name', 'like', 'quote-items.%')
                                                ->orWhere('name', 'like', 'sales.folio-sequences.%')
                                                ->orWhere('name', 'like', 'remissions.%')
                                                ->orWhere('name', 'like', 'remission-items.%');
                                      })
                                      ->get();
            $godRole->givePermissionTo($allPermissions);
        }

        if ($adminRole) {
            // Admin tiene permisos CRUD completos
            $adminPermissions = Permission::where('guard_name', 'api')
                                        ->whereIn('name', [
                                            'customers.index', 'customers.view', 'customers.show', 'customers.store',
                                            'customers.update', 'customers.destroy',
                                            'sales-orders.index', 'sales-orders.view', 'sales-orders.show',
                                            'sales-orders.store', 'sales-orders.update', 'sales-orders.destroy',
                                            'sales-order-items.index', 'sales-order-items.view', 'sales-order-items.show',
                                            'sales-order-items.store', 'sales-order-items.update', 'sales-order-items.destroy',
                                            // SA-M001: Shipments
                                            'shipments.index', 'shipments.view', 'shipments.show',
                                            'shipments.store', 'shipments.update', 'shipments.destroy',
                                            'shipment-items.index', 'shipment-items.view', 'shipment-items.show',
                                            'shipment-items.store', 'shipment-items.update', 'shipment-items.destroy',
                                            // SA-M002: Backorders
                                            'backorders.index', 'backorders.view', 'backorders.show',
                                            'backorders.store', 'backorders.update', 'backorders.destroy',
                                            // SA-M003: Discount Rules
                                            'discount-rules.index', 'discount-rules.view', 'discount-rules.show',
                                            'discount-rules.store', 'discount-rules.update', 'discount-rules.destroy',
                                            // SA-M004: Quotes
                                            'quotes.index', 'quotes.view', 'quotes.show',
                                            'quotes.store', 'quotes.update', 'quotes.destroy',
                                            'quote-items.index', 'quote-items.view', 'quote-items.show',
                                            'quote-items.store', 'quote-items.update', 'quote-items.destroy',
                                            // SA-M005: Folio Sequences (admin only)
                                            'sales.folio-sequences.index', 'sales.folio-sequences.show',
                                            'sales.folio-sequences.update',
                                            // SA-M006: Remissions
                                            'remissions.index', 'remissions.view', 'remissions.show',
                                            'remissions.store', 'remissions.update', 'remissions.destroy',
                                            'remission-items.index', 'remission-items.view', 'remission-items.show',
                                            'remission-items.store', 'remission-items.update', 'remission-items.destroy',
                                        ])->get();
            $adminRole->givePermissionTo($adminPermissions);
        }

        if ($techRole) {
            // Tech tiene permisos CRUD completos (similar a admin)
            $techPermissions = Permission::where('guard_name', 'api')
                                       ->whereIn('name', [
                                           'customers.index', 'customers.view', 'customers.show', 'customers.store',
                                           'customers.update', 'customers.destroy',
                                           'sales-orders.index', 'sales-orders.view', 'sales-orders.show',
                                           'sales-orders.store', 'sales-orders.update', 'sales-orders.destroy',
                                           'sales-order-items.index', 'sales-order-items.view', 'sales-order-items.show',
                                           'sales-order-items.store', 'sales-order-items.update', 'sales-order-items.destroy',
                                           // SA-M001: Shipments
                                           'shipments.index', 'shipments.view', 'shipments.show',
                                           'shipments.store', 'shipments.update', 'shipments.destroy',
                                           'shipment-items.index', 'shipment-items.view', 'shipment-items.show',
                                           'shipment-items.store', 'shipment-items.update', 'shipment-items.destroy',
                                           // SA-M002: Backorders
                                           'backorders.index', 'backorders.view', 'backorders.show',
                                           'backorders.store', 'backorders.update', 'backorders.destroy',
                                           // SA-M003: Discount Rules (read-only for tech)
                                           'discount-rules.index', 'discount-rules.view', 'discount-rules.show',
                                           // SA-M004: Quotes (full access for tech)
                                           'quotes.index', 'quotes.view', 'quotes.show',
                                           'quotes.store', 'quotes.update', 'quotes.destroy',
                                           'quote-items.index', 'quote-items.view', 'quote-items.show',
                                           'quote-items.store', 'quote-items.update', 'quote-items.destroy',
                                           // SA-M006: Remissions (full access for tech)
                                           'remissions.index', 'remissions.view', 'remissions.show',
                                           'remissions.store', 'remissions.update', 'remissions.destroy',
                                           'remission-items.index', 'remission-items.view', 'remission-items.show',
                                           'remission-items.store', 'remission-items.update', 'remission-items.destroy',
                                       ])->get();
            $techRole->givePermissionTo($techPermissions);
        }

        // Customer role - Solo puede ver SUS PROPIOS datos (implementamos lógica específica en el Authorizer)
        $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();
        if ($customerRole) {
            // Customer NO tiene permisos de customers.* - solo acceso a sus propios datos
            $customerPermissions = Permission::where('guard_name', 'api')
                                           ->whereIn('name', [
                                               // NO customers.* permissions - handled by authorizer logic
                                               'sales-orders.index', 'sales-orders.view', 'sales-orders.show',
                                               'sales-order-items.index', 'sales-order-items.view', 'sales-order-items.show',
                                               // SA-M001: Customer can view shipments for their orders
                                               'shipments.index', 'shipments.view', 'shipments.show',
                                               'shipment-items.index', 'shipment-items.view', 'shipment-items.show',
                                               // SA-M002: Customer can view backorders for their orders
                                               'backorders.index', 'backorders.view', 'backorders.show',
                                               // SA-M004: Customer can view and request quotes
                                               'quotes.index', 'quotes.view', 'quotes.show', 'quotes.store',
                                               'quote-items.index', 'quote-items.view', 'quote-items.show',
                                               // SA-M006: Customer can view remissions for their orders
                                               'remissions.index', 'remissions.view', 'remissions.show',
                                               'remission-items.index', 'remission-items.view', 'remission-items.show',
                                           ])->get();
            $customerRole->givePermissionTo($customerPermissions);
        }
    }
}
