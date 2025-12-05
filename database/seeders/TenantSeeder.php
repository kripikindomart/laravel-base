<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Demo Company',
                'slug' => 'demo',
                'subdomain' => 'demo',
                'identification_type' => 'subdomain',
                'status' => 'active',
                'settings' => [
                    'timezone' => 'Asia/Jakarta',
                    'locale' => 'id',
                    'currency' => 'IDR',
                ],
                'meta' => [
                    'industry' => 'Technology',
                    'company_size' => '10-50',
                ],
                'trial_ends_at' => now()->addDays(30),
                'subscription_ends_at' => now()->addYear(),
            ],
            [
                'name' => 'Acme Corporation',
                'slug' => 'acme',
                'subdomain' => 'acme',
                'identification_type' => 'subdomain',
                'status' => 'active',
                'settings' => [
                    'timezone' => 'Asia/Jakarta',
                    'locale' => 'en',
                    'currency' => 'USD',
                ],
                'meta' => [
                    'industry' => 'Retail',
                    'company_size' => '50-200',
                ],
                'trial_ends_at' => null,
                'subscription_ends_at' => now()->addYear(),
            ],
            [
                'name' => 'Tech Startup',
                'slug' => 'techstartup',
                'subdomain' => 'techstartup',
                'identification_type' => 'subdomain',
                'status' => 'active',
                'settings' => [
                    'timezone' => 'Asia/Jakarta',
                    'locale' => 'en',
                    'currency' => 'IDR',
                ],
                'meta' => [
                    'industry' => 'Technology',
                    'company_size' => '1-10',
                ],
                'trial_ends_at' => now()->addDays(14),
                'subscription_ends_at' => null,
            ],
        ];

        foreach ($tenants as $tenantData) {
            $tenant = Tenant::firstOrCreate(
                ['slug' => $tenantData['slug']],
                $tenantData
            );

            $this->command->info("Created tenant: {$tenant->name}");
        }

        $this->command->info('Tenants seeded successfully!');
    }
}
