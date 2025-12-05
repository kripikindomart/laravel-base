<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'administrator',
                'description' => 'Full access to all tenant features',
                'is_default' => false,
                'level' => 100,
                'permissions' => 'all', // Grant all permissions
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage users and view reports',
                'is_default' => false,
                'level' => 80,
                'permissions' => [
                    'view-users', 'create-users', 'edit-users',
                    'view-roles', 'view-permissions',
                    'view-settings',
                    'view-activity-logs', 'view-login-logs',
                    'view-services', 'view-service-logs',
                ],
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Basic user access',
                'is_default' => true,
                'level' => 10,
                'permissions' => [
                    'view-users',
                    'view-settings',
                ],
            ],
        ];

        // Create roles for all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($roles as $roleData) {
                $role = Role::firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'slug' => $roleData['slug'],
                ], [
                    'name' => $roleData['name'],
                    'guard_name' => 'web',
                    'description' => $roleData['description'],
                    'is_default' => $roleData['is_default'],
                    'level' => $roleData['level'],
                ]);

                // Assign permissions to role
                if ($roleData['permissions'] === 'all') {
                    // Grant all permissions to this role
                    $permissions = Permission::where('tenant_id', $tenant->id)->get();
                    $role->permissions()->sync($permissions->pluck('id'));
                } else {
                    // Grant specific permissions
                    $permissions = Permission::where('tenant_id', $tenant->id)
                        ->whereIn('slug', $roleData['permissions'])
                        ->get();
                    $role->permissions()->sync($permissions->pluck('id'));
                }

                $this->command->info("Created role '{$role->name}' for tenant: {$tenant->name}");
            }
        }

        $this->command->info('Roles seeded successfully!');
    }
}
