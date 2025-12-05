<?php

use App\Services\Logging\ActivityLogger;
use App\Services\Logging\ErrorLogger;
use App\Services\Logging\LoginLogger;
use App\Services\Logging\ServiceLogger;

if (!function_exists('activity')) {
    /**
     * Get activity logger instance
     */
    function activity(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }
}

if (!function_exists('serviceLogger')) {
    /**
     * Get service logger instance
     */
    function serviceLogger(): ServiceLogger
    {
        return app(ServiceLogger::class);
    }
}

if (!function_exists('errorLogger')) {
    /**
     * Get error logger instance
     */
    function errorLogger(): ErrorLogger
    {
        return app(ErrorLogger::class);
    }
}

if (!function_exists('loginLogger')) {
    /**
     * Get login logger instance
     */
    function loginLogger(): LoginLogger
    {
        return app(LoginLogger::class);
    }
}
