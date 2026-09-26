<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDatabaseHealth extends Command
{
    protected $signature = 'health:database';

    protected $description = 'Check database connectivity and performance, log any issues';

    public function handle(): int
    {
        $this->info('Checking database health...');

        $issues = [];

        // Test basic connection
        try {
            DB::connection()->getPdo();
            $this->info('  ✓ Database connection OK');
        } catch (\Exception $e) {
            $issues[] = [
                'severity' => 'critical',
                'description' => 'Database connection failed: '.$e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'driver' => config('database.default'),
                    'host' => config('database.connections.'.config('database.default').'.host'),
                ],
            ];
            $this->error('  ✗ Database connection FAILED: '.$e->getMessage());
        }

        // Test query execution
        if (empty($issues)) {
            try {
                DB::select('SELECT 1');
                $this->info('  ✓ Query execution OK');
            } catch (\Exception $e) {
                $issues[] = [
                    'severity' => 'critical',
                    'description' => 'Database query execution failed: '.$e->getMessage(),
                    'details' => [
                        'error' => $e->getMessage(),
                        'query_test' => 'SELECT 1',
                    ],
                ];
                $this->error('  ✗ Query execution FAILED: '.$e->getMessage());
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
                    $this->warn("  ⚠ Slow response time: {$responseTime}ms");
                } else {
                    $this->info("  ✓ Response time: {$responseTime}ms");
                }
            } catch (\Exception $e) {
                // Already caught above
            }
        }

        // Check active connections
        if (empty($issues)) {
            try {
                $driver = config('database.default');
                if ($driver === 'mysql') {
                    $result = DB::select('SHOW STATUS WHERE Variable_name = "Threads_connected"');
                    $connections = $result[0]->Value ?? 0;
                    if ($connections > 50) {
                        $issues[] = [
                            'severity' => 'warning',
                            'description' => "High number of active connections: {$connections}",
                            'details' => [
                                'active_connections' => $connections,
                                'threshold' => 50,
                            ],
                        ];
                        $this->warn("  ⚠ High connections: {$connections}");
                    } else {
                        $this->info("  ✓ Active connections: {$connections}");
                    }
                }
            } catch (\Exception $e) {
                // Not all drivers support this
            }
        }

        // Log any issues found
        foreach ($issues as $issue) {
            ActivityLog::create([
                'action' => 'issue',
                'severity' => $issue['severity'],
                'description' => $issue['description'],
                'new_data' => $issue['details'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'health-check-scheduler',
            ]);
            $this->error("  Logged issue: {$issue['severity']} - {$issue['description']}");
        }

        // If no issues, log a success check
        if (empty($issues)) {
            ActivityLog::create([
                'action' => 'health_check',
                'severity' => 'info',
                'description' => 'Database health check passed. All systems operational.',
                'new_data' => [
                    'driver' => config('database.default'),
                    'status' => 'healthy',
                    'checked_at' => now()->toIso8601String(),
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'health-check-scheduler',
            ]);
            $this->info('  ✓ Health check logged successfully');
        }

        $this->info(empty($issues) ? "\n✓ Database health check PASSED" : "\n✗ Database health check FOUND ".count($issues).' issue(s)');

        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }
}
