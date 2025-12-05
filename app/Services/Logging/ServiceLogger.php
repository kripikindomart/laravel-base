<?php

namespace App\Services\Logging;

use App\Models\Service;
use App\Models\ServiceLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Request;

class ServiceLogger
{
    /**
     * Log service request/response
     */
    public function log(array $data): ServiceLog
    {
        return ServiceLog::create([
            'tenant_id' => tenant()?->id,
            'service_id' => $data['service_id'] ?? null,
            'user_id' => auth()->id(),
            'service_name' => $data['service_name'],
            'service_type' => $data['service_type'] ?? 'external',
            'method' => $data['method'],
            'endpoint' => $data['endpoint'],
            'request_headers' => $data['request_headers'] ?? [],
            'request_body' => $data['request_body'] ?? [],
            'response_status' => $data['response_status'] ?? null,
            'response_headers' => $data['response_headers'] ?? [],
            'response_body' => $data['response_body'] ?? [],
            'duration_ms' => $data['duration_ms'] ?? 0,
            'error_message' => $data['error_message'] ?? null,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log from Service model and Response
     */
    public function logFromService(
        Service $service,
        string $method,
        string $endpoint,
        array $requestData = [],
        ?Response $response = null,
        int $durationMs = 0,
        ?string $error = null
    ): ServiceLog {
        return $this->log([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'service_type' => $service->type,
            'method' => $method,
            'endpoint' => $endpoint,
            'request_headers' => $requestData['headers'] ?? [],
            'request_body' => $requestData['body'] ?? [],
            'response_status' => $response?->status(),
            'response_headers' => $response?->headers() ?? [],
            'response_body' => $response?->json() ?? [],
            'duration_ms' => $durationMs,
            'error_message' => $error,
        ]);
    }

    /**
     * Log successful API call
     */
    public function logSuccess(
        string $serviceName,
        string $method,
        string $endpoint,
        int $responseStatus,
        int $durationMs = 0
    ): ServiceLog {
        return $this->log([
            'service_name' => $serviceName,
            'method' => $method,
            'endpoint' => $endpoint,
            'response_status' => $responseStatus,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Log failed API call
     */
    public function logError(
        string $serviceName,
        string $method,
        string $endpoint,
        string $errorMessage,
        ?int $responseStatus = null
    ): ServiceLog {
        return $this->log([
            'service_name' => $serviceName,
            'method' => $method,
            'endpoint' => $endpoint,
            'response_status' => $responseStatus,
            'error_message' => $errorMessage,
        ]);
    }
}
