<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decaySeconds = 60): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($request, $maxAttempts);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            RateLimiter::remaining($key, $decaySeconds),
            $decaySeconds
        );
    }

    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();

        if ($user) {
            return 'user:'.$user->getAuthIdentifier().':'.sha1($request->ip());
        }

        return sha1($request->ip().'|'.$request->path());
    }

    protected function addHeaders(Response $response, int $maxAttempts, int $remaining, int $retryAfter): Response
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);

        if ($remaining === 0) {
            $response->headers->set('Retry-After', $retryAfter);
        }

        return $response;
    }

    protected function buildResponse(Request $request, int $maxAttempts): Response
    {
        return response()->json([
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => RateLimiter::availableIn($this->resolveRequestSignature($request)),
        ], 429);
    }
}
