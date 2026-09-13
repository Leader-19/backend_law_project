<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $action = $request->get('action');
        $search = trim((string) $request->query('search', ''));
        $userId = $request->query('user_id');

        $query = ActivityLog::with('causer')->latest();

        if ($action) {
            $query->where('action', $action);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($userId) {
            $query->where('causer_id', $userId);
        }

        $logs = $query->paginate($perPage)->withQueryString();

        return Inertia::render('ActivityLog/Index', [
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'filters' => [
                'action' => $action,
                'search' => $search,
                'user_id' => $userId,
            ],
        ]);
    }

    public function destroy(ActivityLog $activityLog)
    {
        $activityLog->delete();

        return back()->with('success', 'Activity log deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:activity_logs,id'],
        ]);

        ActivityLog::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', count($validated['ids']) . ' activity log(s) deleted successfully.');
    }

    public function clearAll()
    {
        ActivityLog::truncate();

        return back()->with('success', 'All activity logs cleared.');
    }

    /**
     * Log a system issue (e.g., database connection failure).
     */
    public function logIssue(Request $request)
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:1000'],
            'severity' => ['required', 'string', 'in:critical,warning,info'],
            'details' => ['nullable', 'array'],
        ]);

        $log = ActivityLog::record(
            'issue',
            $validated['description'],
            null,
            $validated['details'] ?? null,
        );
        $log->update(['severity' => $validated['severity']]);

        return response()->json(['message' => 'System issue logged successfully.'], 201);
    }

    /**
     * Check database connectivity and log issues automatically.
     */
    public function checkDatabaseHealth()
    {
        $issues = [];

        // Test basic connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $issues[] = [
                'severity' => 'critical',
                'description' => 'Database connection failed: ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'driver' => config('database.default'),
                    'host' => config('database.connections.' . config('database.default') . '.host'),
                ],
            ];
        }

        // Test query execution
        if (empty($issues)) {
            try {
                DB::select('SELECT 1');
            } catch (\Exception $e) {
                $issues[] = [
                    'severity' => 'critical',
                    'description' => 'Database query execution failed: ' . $e->getMessage(),
                    'details' => [
                        'error' => $e->getMessage(),
                        'query_test' => 'SELECT 1',
                    ],
                ];
            }
        }

        // Check response time
        if (empty($issues)) {
            $start = microtime(true);
            try {
                DB::select('SELECT 1');
                $responseTime = round((microtime(true) - $start) * 1000, 2);

                if ($responseTime > 1000) {
                    $issues[] = [
                        'severity' => 'warning',
                        'description' => "Database response time is slow: {$responseTime}ms",
                        'details' => [
                            'response_time_ms' => $responseTime,
                            'threshold_ms' => 1000,
                        ],
                    ];
                }
            } catch (\Exception $e) {
                // Already caught above
            }
        }

        // Check disk space (approximate via table sizes)
        if (empty($issues)) {
            try {
                $tableCount = count(DB::select('SHOW TABLES'));
                if ($tableCount > 200) {
                    $issues[] = [
                        'severity' => 'warning',
                        'description' => "High number of tables detected: {$tableCount}",
                        'details' => [
                            'table_count' => $tableCount,
                        ],
                    ];
                }
            } catch (\Exception $e) {
                // Not all drivers support SHOW TABLES
            }
        }

        // Log any issues found
        foreach ($issues as $issue) {
            $log = ActivityLog::record('issue', $issue['description'], null, $issue['details']);
            $log->update(['severity' => $issue['severity']]);
        }

        // If no issues, log a success check
        if (empty($issues)) {
            // Optionally log healthy status (set action to 'health_check')
            $log = ActivityLog::record(
                'health_check',
                'Database health check passed. All systems operational.',
                null,
                [
                    'driver' => config('database.default'),
                    'status' => 'healthy',
                    'checked_at' => now()->toIso8601String(),
                ],
            );
            $log->update(['severity' => 'info']);
        }

        return response()->json([
            'status' => empty($issues) ? 'healthy' : 'issues_found',
            'issues' => $issues,
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
