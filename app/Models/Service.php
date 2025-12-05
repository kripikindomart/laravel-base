<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'endpoint',
        'auth_type',
        'description',
        'default_config',
        'headers',
        'is_active',
        'timeout',
        'retry_attempts',
    ];

    protected $casts = [
        'default_config' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'timeout' => 'integer',
        'retry_attempts' => 'integer',
    ];

    /**
     * Get service configs for tenants
     */
    public function configs(): HasMany
    {
        return $this->hasMany(ServiceConfig::class);
    }

    /**
     * Get service logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ServiceLog::class);
    }

    /**
     * Check if service is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if service is internal
     */
    public function isInternal(): bool
    {
        return $this->type === 'internal';
    }

    /**
     * Check if service is external
     */
    public function isExternal(): bool
    {
        return $this->type === 'external';
    }
}
