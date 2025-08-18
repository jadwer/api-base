<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔐 Seeding Contacts permissions...');
        
        // Create permissions for Contacts
        $contactPermissions = [
            'contacts.index',
            'contacts.show', 
            'contacts.store',
            'contacts.update',
            'contacts.destroy',
        ];

        foreach ($contactPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create permissions for Contact Addresses
        $contactAddressPermissions = [
            'contact-addresses.index',
            'contact-addresses.show',
            'contact-addresses.store', 
            'contact-addresses.update',
            'contact-addresses.destroy',
        ];

        foreach ($contactAddressPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create permissions for Contact Documents
        $contactDocumentPermissions = [
            'contact-documents.index',
            'contact-documents.show',
            'contact-documents.store',
            'contact-documents.update', 
            'contact-documents.destroy',
        ];

        foreach ($contactDocumentPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create permissions for Contact People
        $contactPersonPermissions = [
            'contact-people.index',
            'contact-people.show',
            'contact-people.store',
            'contact-people.update',
            'contact-people.destroy',
        ];

        foreach ($contactPersonPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        $techRole = Role::where('name', 'tech')->where('guard_name', 'api')->first();
        $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();

        if ($adminRole) {
            // Admin gets all permissions
            $allPermissions = array_merge(
                $contactPermissions,
                $contactAddressPermissions, 
                $contactDocumentPermissions,
                $contactPersonPermissions
            );
            $adminRole->givePermissionTo($allPermissions);
            $this->command->info("✅ Admin role assigned all Contacts permissions");
        }

        if ($techRole) {
            // Tech gets read-only permissions
            $readOnlyPermissions = [
                'contacts.index', 'contacts.show',
                'contact-addresses.index', 'contact-addresses.show',
                'contact-documents.index', 'contact-documents.show', 
                'contact-people.index', 'contact-people.show',
            ];
            $techRole->givePermissionTo($readOnlyPermissions);
            $this->command->info("✅ Tech role assigned read-only Contacts permissions");
        }

        if ($customerRole) {
            // Customer gets no permissions by default
            $this->command->info("✅ Customer role has no Contacts permissions (as expected)");
        }
        
        $this->command->info('✅ Contacts permissions seeded successfully!');
    }
}
