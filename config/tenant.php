<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | Configure how tenants are identified in your application.
    | Supported: "subdomain", "domain", "path"
    |
    */

    'identification' => [
        // Primary identification method
        'default' => env('TENANT_IDENTIFICATION', 'subdomain'),

        // Enable custom domains for tenants
        'custom_domain_enabled' => env('TENANT_CUSTOM_DOMAIN_ENABLED', true),

        // Your main application domain
        'central_domain' => env('APP_DOMAIN', 'localhost'),

        // Subdomain for central/super admin
        'central_subdomain' => env('CENTRAL_SUBDOMAIN', 'admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Domain Configuration
    |--------------------------------------------------------------------------
    */

    'custom_domain' => [
        // Require domain verification before use
        'require_verification' => env('TENANT_DOMAIN_VERIFICATION', true),

        // DNS records required for verification
        'dns_verification' => [
            'type' => 'TXT',
            'name' => '_tenant_verify',
            // Value will be generated per tenant
        ],

        // Allowed TLDs for custom domains (null = allow all)
        'allowed_tlds' => null, // ['com', 'id', 'co.id', 'net', 'org']

        // Blocked domains (security)
        'blocked_domains' => [
            'localhost',
            '127.0.0.1',
            'local.test',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */

    'database' => [
        // Use separate database per tenant
        'separate' => env('TENANT_SEPARATE_DB', false),

        // Database prefix for tenant databases
        'prefix' => env('TENANT_DB_PREFIX', 'tenant_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */

    'storage' => [
        // Storage disk for tenant files
        'disk' => env('TENANT_STORAGE_DISK', 'public'),

        // Separate storage path per tenant
        'separate_path' => env('TENANT_SEPARATE_STORAGE', true),

        // Path prefix
        'path_prefix' => 'tenants/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        // Cache tenant data
        'enabled' => env('TENANT_CACHE_ENABLED', true),

        // Cache TTL in seconds
        'ttl' => env('TENANT_CACHE_TTL', 3600),

        // Cache key prefix
        'prefix' => 'tenant_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    'features' => [
        // Allow tenants to have multiple domains
        'multiple_domains' => env('TENANT_MULTIPLE_DOMAINS', false),

        // SSL requirement for custom domains
        'require_ssl' => env('TENANT_REQUIRE_SSL', true),

        // Auto-redirect www to non-www (or vice versa)
        'www_redirect' => env('TENANT_WWW_REDIRECT', 'remove'), // 'add', 'remove', 'none'
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */

    'limits' => [
        // Maximum tenants per system (0 = unlimited)
        'max_tenants' => env('TENANT_MAX_TENANTS', 0),

        // Maximum users per tenant (0 = unlimited)
        'max_users_per_tenant' => env('TENANT_MAX_USERS', 100),

        // Maximum storage per tenant in MB (0 = unlimited)
        'max_storage_mb' => env('TENANT_MAX_STORAGE_MB', 1000),
    ],

];
