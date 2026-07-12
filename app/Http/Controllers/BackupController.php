<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function download(): StreamedResponse
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $dbName = $config['database'];
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;

        $mysqlDump = trim(shell_exec('which mysqldump'));

        if ($mysqlDump && file_exists($mysqlDump)) {
            $fileName = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
            $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

            $command = sprintf(
                '%s --skip-column-statistics --host=%s --port=%s --user=%s %s %s > %s 2>/dev/null',
                escapeshellcmd($mysqlDump),
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                $password ? '--password=' . escapeshellarg($password) : '',
                escapeshellarg($dbName),
                escapeshellarg($tempPath)
            );

            passthru($command);

            if (file_exists($tempPath)) {
                return response()->streamDownload(function () use ($tempPath) {
                    try {
                        readfile($tempPath);
                    } finally {
                        File::delete($tempPath);
                    }
                }, $fileName, [
                    'Content-Type' => 'application/sql',
                ]);
            }
        }

        $fileName = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';

        return response()->streamDownload(function () {
            echo $this->generateSql();
        }, $fileName, [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function generateSql(): string
    {
        $output = '';
        $output .= "-- Law Sharing Documents SQL Backup\n";
        $output .= "-- Generated: " . now()->format('Y-m-d H:i:s T') . "\n\n";

        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

            $create = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createSql = $create[0]->{'Create Table'} ?? $create[0]->{'create table'} ?? '';
            $output .= $createSql . ";\n\n";

            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $columns = [];
                $values = [];

                foreach ($row as $col => $val) {
                    if ($val === null) {
                        $columns[] = "`{$col}`";
                        $values[] = 'NULL';
                    } else {
                        $columns[] = "`{$col}`";
                        $values[] = "'" . addcslashes((string) $val, "\\'\"\0\n\r") . "'";
                    }
                }

                $output .= "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }

            $output .= "\n";
        }

        return $output;
    }
}
