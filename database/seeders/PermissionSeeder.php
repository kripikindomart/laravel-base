<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User Management
            ['group' => 'users', 'name' => 'View Users', 'slug' => 'view-users', 'description' => 'Can view users list'],
            ['group' => 'users', 'name' => 'Create Users', 'slug' => 'create-users', 'description' => 'Can create new users'],
            ['group' => 'users', 'name' => 'Edit Users', 'slug' => 'edit-users', 'description' => 'Can edit existing users'],
            ['group' => 'users', 'name' => 'Delete Users', 'slug' => 'delete-users', 'description' => 'Can delete users'],

            // Role Management
            ['group' => 'roles', 'name' => 'View Roles', 'slug' => 'view-roles', 'description' => 'Can view roles list'],
            ['group' => 'roles', 'name' => 'Create Roles', 'slug' => 'create-roles', 'description' => 'Can create new roles'],
            ['group' => 'roles', 'name' => 'Edit Roles', 'slug' => 'edit-roles', 'description' => 'Can edit existing roles'],
            ['group' => 'roles', 'name' => 'Delete Roles', 'slug' => 'delete-roles', 'description' => 'Can delete roles'],

            // Permission Management
            ['group' => 'permissions', 'name' => 'View Permissions', 'slug' => 'view-permissions', 'description' => 'Can view permissions list'],
            ['group' => 'permissions', 'name' => 'Assign Permissions', 'slug' => 'assign-permissions', 'description' => 'Can assign permissions to roles/users'],

            // Settings
            ['group' => 'settings', 'name' => 'View Settings', 'slug' => 'view-settings', 'description' => 'Can view tenant settings'],
            ['group' => 'settings', 'name' => 'Edit Settings', 'slug' => 'edit-settings', 'description' => 'Can edit tenant settings'],

            // Activity Logs
            ['group' => 'logs', 'name' => 'View Activity Logs', 'slug' => 'view-activity-logs', 'description' => 'Can view activity logs'],
            ['group' => 'logs', 'name' => 'View Error Logs', 'slug' => 'view-error-logs', 'description' => 'Can view error logs'],
            ['group' => 'logs', 'name' => 'View Login Logs', 'slug' => 'view-login-logs', 'description' => 'Can view login logs'],

            // Services
            ['group' => 'services', 'name' => 'View Services', 'slug' => 'view-services', 'description' => 'Can view services'],
            ['group' => 'services', 'name' => 'Manage Services', 'slug' => 'manage-services', 'description' => 'Can configure and manage services'],
            ['group' => 'services', 'name' => 'View Service Logs', 'slug' => 'view-service-logs', 'description' => 'Can view service logs'],
        ];

        // Create permissions for all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($permissions as $permissionData) {
                Permission::firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'slug' => $permissionData['slug'],
                ], [
                    'name' => $permissionData['name'],
                    'guard_name' => 'web',
                    'description' => $permissionData['description'],
                    'group' => $permissionData['group'],
                ]);
            }

            $this->command->info("Created permissions for tenant: {$tenant->name}");
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
