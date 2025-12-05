<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');
        $this->command->info('');

        // Seed in correct order:
        // 1. Tenants first
        // 2. Permissions (requires tenants)
        // 3. Roles (requires tenants and permissions)
        // 4. Users (requires tenants and roles)

        $this->call([
            TenantSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SuperAdminSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('Database seeding completed successfully!');
    }
}
