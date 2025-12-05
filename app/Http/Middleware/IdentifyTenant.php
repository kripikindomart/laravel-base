<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->identifyTenant($request);

        if ($tenant) {
            // Check if tenant is active
            if (!$tenant->isActive()) {
                abort(403, 'Tenant is not active.');
            }

            // Set current tenant
            setTenant($tenant);

            // Share tenant with views
            view()->share('tenant', $tenant);
        }

        return $next($request);
    }

    /**
     * Identify tenant from request
     */
    protected function identifyTenant(Request $request): ?Tenant
    {
        $host = $request->getHost();

        // Try to identify from cache first
        $cacheKey = 'tenant_' . md5($host);

        if (config('tenant.cache.enabled')) {
            $tenant = Cache::remember($cacheKey, config('tenant.cache.ttl'), function () use ($host, $request) {
                return $this->findTenantByHost($host, $request);
            });
        } else {
            $tenant = $this->findTenantByHost($host, $request);
        }

        return $tenant;
    }

    /**
     * Find tenant by host
     */
    protected function findTenantByHost(string $host, Request $request): ?Tenant
    {
        $centralDomain = config('tenant.identification.central_domain');
        $centralSubdomain = config('tenant.identification.central_subdomain');

        // Skip tenant identification for central/admin domain
        if ($host === $centralDomain || $host === "{$centralSubdomain}.{$centralDomain}") {
            return null;
        }

        // 1. Try Custom Domain (Highest Priority)
        if (config('tenant.identification.custom_domain_enabled')) {
            $tenant = Tenant::where('domain', $host)
                ->where('status', 'active')
                ->first();

            if ($tenant) {
                return $tenant;
            }
        }

        // 2. Try Subdomain
        if ($this->isSubdomain($host, $centralDomain)) {
            $subdomain = $this->extractSubdomain($host, $centralDomain);

            $tenant = Tenant::where('subdomain', $subdomain)
                ->where('identification_type', 'subdomain')
                ->where('status', 'active')
                ->first();

            if ($tenant) {
                return $tenant;
            }
        }

        // 3. Try Path-based (from first segment)
        $pathSegment = $request->segment(1);

        if ($pathSegment) {
            $tenant = Tenant::where('slug', $pathSegment)
                ->where('identification_type', 'path')
                ->where('status', 'active')
                ->first();

            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Check if host is a subdomain
     */
    protected function isSubdomain(string $host, string $centralDomain): bool
    {
        return str_ends_with($host, ".{$centralDomain}") && $host !== $centralDomain;
    }

    /**
     * Extract subdomain from host
     */
    protected function extractSubdomain(string $host, string $centralDomain): string
    {
        return str_replace(".{$centralDomain}", '', $host);
    }
}
