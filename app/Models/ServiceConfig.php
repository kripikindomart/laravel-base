<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceConfig extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'service_id',
        'config',
        'credentials',
        'is_enabled',
        'rate_limit',
    ];

    protected $casts = [
        'config' => 'array',
        'credentials' => 'encrypted:array',
        'is_enabled' => 'boolean',
        'rate_limit' => 'integer',
    ];

    /**
     * Get tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get service
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Check if config is enabled
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled === true && $this->service->isActive();
    }

    /**
     * Get merged config (default + tenant config)
     */
    public function getMergedConfig(): array
    {
        $defaultConfig = $this->service->default_config ?? [];
        $tenantConfig = $this->config ?? [];

        return array_merge($defaultConfig, $tenantConfig);
    }
}
