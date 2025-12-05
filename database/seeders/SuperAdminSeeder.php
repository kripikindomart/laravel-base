<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin (not tied to any tenant)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
                'status' => 'active',
                'tenant_id' => null,
            ]
        );

        $this->command->info("Created Super Admin: {$superAdmin->email}");

        // Create demo users for each tenant
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Create tenant admin
            $adminRole = Role::where('tenant_id', $tenant->id)
                ->where('slug', 'administrator')
                ->first();

            $tenantAdmin = User::firstOrCreate(
                ['email' => "admin@{$tenant->slug}.com"],
                [
                    'name' => "{$tenant->name} Admin",
                    'password' => Hash::make('password'),
                    'is_super_admin' => false,
                    'status' => 'active',
                    'tenant_id' => $tenant->id,
                ]
            );

            if ($adminRole) {
                $tenantAdmin->assignRole($adminRole);
            }

            $this->command->info("Created Admin for {$tenant->name}: {$tenantAdmin->email}");

            // Create tenant manager
            $managerRole = Role::where('tenant_id', $tenant->id)
                ->where('slug', 'manager')
                ->first();

            $tenantManager = User::firstOrCreate(
                ['email' => "manager@{$tenant->slug}.com"],
                [
                    'name' => "{$tenant->name} Manager",
                    'password' => Hash::make('password'),
                    'is_super_admin' => false,
                    'status' => 'active',
                    'tenant_id' => $tenant->id,
                ]
            );

            if ($managerRole) {
                $tenantManager->assignRole($managerRole);
            }

            $this->command->info("Created Manager for {$tenant->name}: {$tenantManager->email}");

            // Create regular user
            $userRole = Role::where('tenant_id', $tenant->id)
                ->where('slug', 'user')
                ->first();

            $regularUser = User::firstOrCreate(
                ['email' => "user@{$tenant->slug}.com"],
                [
                    'name' => "{$tenant->name} User",
                    'password' => Hash::make('password'),
                    'is_super_admin' => false,
                    'status' => 'active',
                    'tenant_id' => $tenant->id,
                ]
            );

            if ($userRole) {
                $regularUser->assignRole($userRole);
            }

            $this->command->info("Created User for {$tenant->name}: {$regularUser->email}");
        }

        $this->command->info('');
        $this->command->info('==============================================');
        $this->command->info('Users seeded successfully!');
        $this->command->info('==============================================');
        $this->command->info('Super Admin Login:');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
        $this->command->info('==============================================');
        $this->command->info('Tenant Admin Logins (password: password):');
        foreach ($tenants as $tenant) {
            $this->command->info("- admin@{$tenant->slug}.com ({$tenant->name})");
        }
        $this->command->info('==============================================');
    }
}
