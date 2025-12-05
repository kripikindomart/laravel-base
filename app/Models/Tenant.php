<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'identification_type',
        'database_name',
        'status',
        'settings',
        'meta',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'meta' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Get users for this tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get roles for this tenant
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get permissions for this tenant
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Get service configs for this tenant
     */
    public function serviceConfigs(): HasMany
    {
        return $this->hasMany(ServiceConfig::class);
    }

    /**
     * Get activity logs for this tenant
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is active
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    /**
     * Get tenant identifier based on type
     */
    public function getIdentifier(): string
    {
        return match($this->identification_type) {
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'path' => $this->slug,
            default => $this->slug,
        };
    }

    /**
     * Check if tenant uses custom domain
     */
    public function hasCustomDomain(): bool
    {
        return !empty($this->domain) && $this->identification_type === 'domain';
    }

    /**
     * Check if custom domain is verified
     */
    public function isDomainVerified(): bool
    {
        return ($this->meta['domain_verified'] ?? false) === true;
    }

    /**
     * Get full URL for tenant
     */
    public function getUrl(string $path = ''): string
    {
        $protocol = config('tenant.features.require_ssl') ? 'https://' : 'http://';
        $domain = $this->getPrimaryDomain();

        return $protocol . $domain . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get primary domain for tenant
     */
    public function getPrimaryDomain(): string
    {
        if ($this->hasCustomDomain()) {
            return $this->domain;
        }

        if ($this->identification_type === 'subdomain') {
            return $this->subdomain . '.' . config('tenant.identification.central_domain');
        }

        // Path-based fallback to central domain
        return config('tenant.identification.central_domain');
    }

    /**
     * Set custom domain for tenant
     */
    public function setCustomDomain(string $domain): bool
    {
        $domainService = app(\App\Services\DomainService::class);
        $validation = $domainService->validateDomain($domain);

        if (!$validation['valid']) {
            return false;
        }

        $this->update([
            'domain' => $validation['domain'],
            'identification_type' => 'domain',
        ]);

        // Generate verification token
        $domainService->generateVerificationToken($this);

        return true;
    }

    /**
     * Remove custom domain
     */
    public function removeCustomDomain(): void
    {
        // Clear domain verification meta
        $meta = $this->meta ?? [];
        unset($meta['domain_verified'], $meta['domain_verified_at']);

        // Clear domain settings
        $settings = $this->settings ?? [];
        unset($settings['domain_verification_token'], $settings['domain_verification_expires_at']);

        $this->update([
            'domain' => null,
            'identification_type' => 'subdomain', // Fallback to subdomain
            'meta' => $meta,
            'settings' => $settings,
        ]);
    }

    /**
     * Get DNS instructions for custom domain setup
     */
    public function getDnsInstructions(): array
    {
        $domainService = app(\App\Services\DomainService::class);
        return $domainService->getDnsInstructions($this);
    }

    /**
     * Verify custom domain
     */
    public function verifyDomain(): bool
    {
        $domainService = app(\App\Services\DomainService::class);
        return $domainService->verifyDomain($this);
    }

    /**
     * Check SSL status for custom domain
     */
    public function checkSsl(): array
    {
        if (!$this->hasCustomDomain()) {
            return ['has_ssl' => false, 'error' => 'No custom domain configured'];
        }

        $domainService = app(\App\Services\DomainService::class);
        return $domainService->checkSsl($this->domain);
    }
}
