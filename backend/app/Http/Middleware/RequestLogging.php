<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLogging
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log request
        $this->logRequest($request);

        $response = $next($request);

        // Log response
        $this->logResponse($request, $response, $startTime);

        return $response;
    }

    /**
     * Log incoming request
     */
    protected function logRequest(Request $request): void
    {
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
        ];

        // Don't log sensitive data in production
        if (!app()->environment('production')) {
            $context['payload'] = $this->filterSensitiveData($request->all());
        }

        Log::info('Incoming Request', $context);
    }

    /**
     * Log response
     */
    protected function logResponse(Request $request, Response $response, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
        ];

        $level = $this->getLogLevel($response->getStatusCode());

        Log::log($level, 'Request Completed', $context);

        // Log slow requests
        if ($duration > 1000) {
            Log::warning('Slow Request Detected', $context);
        }
    }

    /**
     * Filter sensitive data from payload
     */
    protected function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'api_key', 'credit_card'];

        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***FILTERED***';
            }
        }

        return $data;
    }

    /**
     * Get appropriate log level based on status code
     */
    protected function getLogLevel(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            default => 'info',
        };
    }
}
