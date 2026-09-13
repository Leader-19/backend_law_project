<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * Attach a correlation ID to every API response and log state-changing
     * requests. Read-only successes are intentionally omitted to keep
     * production logs useful and affordable.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-ID') ?: (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);
        $startedAt = microtime(true);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        $status = $response->getStatusCode();
        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $status,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
        ];

        if ($status >= 400 && $status < 500) {
            Log::warning('API client request failed', $context);
        } elseif (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) && $status < 400) {
            Log::info('API request completed', $context);
        }

        return $response;
    }
}
