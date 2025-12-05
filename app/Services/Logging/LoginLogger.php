<?php

namespace App\Services\Logging;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class LoginLogger
{
    /**
     * Log successful login
     */
    public function logSuccess(User $user): LoginLog
    {
        return LoginLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'status' => 'success',
            'location' => $this->getLocationFromIp(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log failed login
     */
    public function logFailed(string $email, string $reason = 'Invalid credentials'): LoginLog
    {
        return LoginLog::create([
            'tenant_id' => tenant()?->id,
            'user_id' => null,
            'email' => $email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'status' => 'failed',
            'failure_reason' => $reason,
            'location' => $this->getLocationFromIp(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log blocked login
     */
    public function logBlocked(string $email, string $reason = 'Too many attempts'): LoginLog
    {
        return LoginLog::create([
            'tenant_id' => tenant()?->id,
            'user_id' => null,
            'email' => $email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'status' => 'blocked',
            'failure_reason' => $reason,
            'location' => $this->getLocationFromIp(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get location from IP (simple implementation)
     * For production, use a service like ip-api.com or MaxMind
     */
    protected function getLocationFromIp(): array
    {
        $ip = Request::ip();

        // Skip for local IPs
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return [
                'country' => 'Local',
                'city' => 'Local',
            ];
        }

        // In production, integrate with IP geolocation service
        // For now, return empty
        return [];
    }

    /**
     * Check if IP should be blocked (too many failed attempts)
     */
    public function shouldBlockIp(string $ip, int $maxAttempts = 5, int $withinMinutes = 15): bool
    {
        $recentFailedAttempts = LoginLog::where('ip_address', $ip)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes($withinMinutes))
            ->count();

        return $recentFailedAttempts >= $maxAttempts;
    }
}
