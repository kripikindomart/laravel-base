<?php

namespace App\Services\Logging;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Request;
use Throwable;

class ErrorLogger
{
    /**
     * Log an exception
     */
    public function log(Throwable $exception, array $context = [], string $severity = 'medium'): ErrorLog
    {
        return ErrorLog::create([
            'tenant_id' => tenant()?->id,
            'user_id' => auth()->id(),
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode() ? (string) $exception->getCode() : null,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'severity' => $severity,
            'is_resolved' => false,
            'created_at' => now(),
        ]);
    }

    /**
     * Log critical error
     */
    public function critical(Throwable $exception, array $context = []): ErrorLog
    {
        return $this->log($exception, $context, 'critical');
    }

    /**
     * Log high severity error
     */
    public function high(Throwable $exception, array $context = []): ErrorLog
    {
        return $this->log($exception, $context, 'high');
    }

    /**
     * Log medium severity error
     */
    public function medium(Throwable $exception, array $context = []): ErrorLog
    {
        return $this->log($exception, $context, 'medium');
    }

    /**
     * Log low severity error
     */
    public function low(Throwable $exception, array $context = []): ErrorLog
    {
        return $this->log($exception, $context, 'low');
    }

    /**
     * Log custom error (without exception object)
     */
    public function logCustom(
        string $errorType,
        string $errorMessage,
        array $context = [],
        string $severity = 'medium'
    ): ErrorLog {
        return ErrorLog::create([
            'tenant_id' => tenant()?->id,
            'user_id' => auth()->id(),
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'context' => $context,
            'severity' => $severity,
            'is_resolved' => false,
            'created_at' => now(),
        ]);
    }
}
