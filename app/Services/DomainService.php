<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DomainService
{
    /**
     * Validate custom domain
     */
    public function validateDomain(string $domain): array
    {
        $errors = [];

        // Remove protocol if present
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = trim($domain, '/');

        // Basic domain format validation
        if (!filter_var('http://' . $domain, FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid domain format.';
        }

        // Check if domain is localhost or IP
        if (in_array($domain, config('tenant.custom_domain.blocked_domains'))) {
            $errors[] = 'This domain is not allowed.';
        }

        // Check if domain is already used
        if (Tenant::where('domain', $domain)->exists()) {
            $errors[] = 'This domain is already in use.';
        }

        // Check TLD restrictions
        $allowedTlds = config('tenant.custom_domain.allowed_tlds');
        if ($allowedTlds !== null) {
            $tld = $this->extractTld($domain);
            if (!in_array($tld, $allowedTlds)) {
                $errors[] = 'This domain TLD is not allowed.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'domain' => $domain,
        ];
    }

    /**
     * Generate verification token for domain
     */
    public function generateVerificationToken(Tenant $tenant): string
    {
        $token = Str::random(32);

        // Store token in tenant settings
        $settings = $tenant->settings ?? [];
        $settings['domain_verification_token'] = $token;
        $settings['domain_verification_expires_at'] = now()->addDays(7)->toDateTimeString();

        $tenant->update(['settings' => $settings]);

        return $token;
    }

    /**
     * Verify domain ownership via DNS TXT record
     */
    public function verifyDomain(Tenant $tenant): bool
    {
        if (!$tenant->domain) {
            return false;
        }

        $expectedToken = $tenant->settings['domain_verification_token'] ?? null;

        if (!$expectedToken) {
            return false;
        }

        // Check if verification token has expired
        $expiresAt = $tenant->settings['domain_verification_expires_at'] ?? null;
        if ($expiresAt && now()->isAfter($expiresAt)) {
            return false;
        }

        // Get DNS TXT records
        $txtRecords = $this->getDnsTxtRecords($tenant->domain);

        // Look for verification record
        $verificationName = config('tenant.custom_domain.dns_verification.name', '_tenant_verify');
        $expectedValue = "tenant-verification={$expectedToken}";

        foreach ($txtRecords as $record) {
            if (str_contains($record, $expectedValue)) {
                // Mark domain as verified
                $this->markDomainAsVerified($tenant);
                return true;
            }
        }

        return false;
    }

    /**
     * Get DNS TXT records for domain
     */
    protected function getDnsTxtRecords(string $domain): array
    {
        $records = [];

        try {
            $dnsRecords = dns_get_record($domain, DNS_TXT);

            if ($dnsRecords !== false) {
                foreach ($dnsRecords as $record) {
                    if (isset($record['txt'])) {
                        $records[] = $record['txt'];
                    }
                }
            }
        } catch (\Exception $e) {
            // DNS lookup failed
            logger()->error('DNS lookup failed for domain: ' . $domain, [
                'error' => $e->getMessage(),
            ]);
        }

        return $records;
    }

    /**
     * Mark domain as verified
     */
    protected function markDomainAsVerified(Tenant $tenant): void
    {
        $meta = $tenant->meta ?? [];
        $meta['domain_verified'] = true;
        $meta['domain_verified_at'] = now()->toDateTimeString();

        $tenant->update(['meta' => $meta]);

        // Clear cache
        Cache::forget('tenant_' . md5($tenant->domain));
    }

    /**
     * Check if domain is verified
     */
    public function isDomainVerified(Tenant $tenant): bool
    {
        return ($tenant->meta['domain_verified'] ?? false) === true;
    }

    /**
     * Get SSL certificate status for domain
     */
    public function checkSsl(string $domain): array
    {
        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$domain}:443",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($client === false) {
                return [
                    'has_ssl' => false,
                    'error' => $errstr,
                ];
            }

            $params = stream_context_get_params($client);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

            fclose($client);

            return [
                'has_ssl' => true,
                'issuer' => $cert['issuer']['CN'] ?? 'Unknown',
                'valid_from' => date('Y-m-d H:i:s', $cert['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $cert['validTo_time_t']),
                'is_valid' => $cert['validTo_time_t'] > time(),
            ];
        } catch (\Exception $e) {
            return [
                'has_ssl' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract TLD from domain
     */
    protected function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);

        // Handle multi-level TLDs (e.g., co.id, com.au)
        if (count($parts) >= 3) {
            $possibleTld = implode('.', array_slice($parts, -2));
            $knownMultiLevelTlds = ['co.id', 'com.au', 'co.uk', 'com.br'];

            if (in_array($possibleTld, $knownMultiLevelTlds)) {
                return $possibleTld;
            }
        }

        return end($parts);
    }

    /**
     * Get DNS instructions for tenant
     */
    public function getDnsInstructions(Tenant $tenant): array
    {
        $verificationToken = $tenant->settings['domain_verification_token'] ?? $this->generateVerificationToken($tenant);
        $appIp = gethostbyname(config('app.url'));

        return [
            'verification' => [
                'type' => 'TXT',
                'name' => config('tenant.custom_domain.dns_verification.name', '_tenant_verify'),
                'value' => "tenant-verification={$verificationToken}",
                'ttl' => 3600,
            ],
            'a_record' => [
                'type' => 'A',
                'name' => '@',
                'value' => $appIp,
                'ttl' => 3600,
            ],
            'cname_record' => [
                'type' => 'CNAME',
                'name' => 'www',
                'value' => $tenant->domain,
                'ttl' => 3600,
            ],
        ];
    }
}
