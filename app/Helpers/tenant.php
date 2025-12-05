<?php

use App\Models\Tenant;

if (!function_exists('tenant')) {
    /**
     * Get current tenant
     */
    function tenant(): ?Tenant
    {
        return app('current.tenant');
    }
}

if (!function_exists('setTenant')) {
    /**
     * Set current tenant
     */
    function setTenant(?Tenant $tenant): void
    {
        app()->instance('current.tenant', $tenant);
    }
}
