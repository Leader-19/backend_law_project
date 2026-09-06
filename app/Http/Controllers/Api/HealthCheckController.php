<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * GET /api/health
     *
     * Returns the status of all critical services.
     * Use this endpoint for uptime monitoring, load balancer checks,
     * and post-deploy verification.
     */
    public function __invoke()
    {
        $checks = [];
        $healthy = true;

        // 1. Database
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $checks['database'] = [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'connection' => config('database.default'),
            ];
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['database'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        // 2. Cache
        try {
            $start = microtime(true);
            $cacheKey = '_health_check_' . now()->timestamp;
            Cache::put($cacheKey, true, 10);
            $value = Cache::get($cacheKey);
            Cache::forget($cacheKey);
            $checks['cache'] = [
                'status' => $value === true ? 'ok' : 'error',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'driver' => config('cache.default'),
            ];
            if ($value !== true) {
                $healthy = false;
                $checks['cache']['message'] = 'Cache value mismatch';
            }
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['cache'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        // 3. Storage (public disk)
        try {
            $start = microtime(true);
            $testFile = '_health_check_' . now()->timestamp . '.txt';
            Storage::disk('public')->put($testFile, 'ok');
            $exists = Storage::disk('public')->exists($testFile);
            Storage::disk('public')->delete($testFile);
            $checks['storage'] = [
                'status' => $exists ? 'ok' : 'error',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'disk' => 'public',
            ];
            if (! $exists) {
                $healthy = false;
                $checks['storage']['message'] = 'File write/read failed';
            }
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['storage'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        // 4. Queue (Redis)
        if (config('queue.default') === 'redis') {
            try {
                $start = microtime(true);
                $redis = Redis::connection('default');
                $redis->ping();
                $checks['queue'] = [
                    'status' => 'ok',
                    'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                    'driver' => 'redis',
                ];
            } catch (\Throwable $e) {
                $healthy = false;
                $checks['queue'] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // 5. Spatie Permissions table
        try {
            DB::select('SELECT COUNT(*) AS cnt FROM permissions');
            $checks['permissions_table'] = [
                'status' => 'ok',
            ];
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['permissions_table'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        $response = [
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'checks' => $checks,
        ];

        return response()->json($response, $healthy ? 200 : 503);
    }
}
