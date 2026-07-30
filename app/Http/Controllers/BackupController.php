<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class BackupController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();

        return Inertia::render('Backup/BackupIndex', [
            'categories' => $categories,
        ]);
    }

    public function backup(Request $request): StreamedResponse
    {
        $request->validate([
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $categoryIds = $request->input('category_ids', []);

        $backupName = 'backup_' . now()->format('Y-m-d_H-i-s');
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $backupName;
        File::makeDirectory($tempDir);

        $this->exportDatabase($tempDir);

        $documents = Document::when($categoryIds, function ($query, $ids) {
                $query->whereIn('category_id', $ids);
            })
            ->with('category')
            ->get();

        $this->exportFiles($tempDir, $documents);

        $zipPath = $this->createZip($tempDir, $backupName);

        return response()->streamDownload(function () use ($zipPath, $tempDir) {
            try {
                readfile($zipPath);
            } finally {
                File::deleteDirectory($tempDir);
                File::delete($zipPath);
            }
        }, $backupName . '.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    private function exportDatabase(string $tempDir): void
    {
        $sql = $this->generateSql();
        file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'database.sql', $sql);
    }

    private function exportFiles(string $tempDir, $documents): void
    {
        $categoriesDir = $tempDir . DIRECTORY_SEPARATOR . 'categories';
        File::makeDirectory($categoriesDir);

        foreach ($documents as $document) {
            $categoryName = $document->category?->title ?? 'uncategorized';
            $categoryDir = $categoriesDir . DIRECTORY_SEPARATOR . $this->sanitizeDirName($categoryName);

            if (!File::isDirectory($categoryDir)) {
                File::makeDirectory($categoryDir, 0755, true, true);
            }

            if ($document->doc_upload && Storage::disk('public')->exists($document->doc_upload)) {
                $ext = pathinfo($document->doc_upload, PATHINFO_EXTENSION);
                $safeName = $this->sanitizeFileName($document->doc_name) . '.' . $ext;
                $dest = $categoryDir . DIRECTORY_SEPARATOR . $safeName;
                if (!File::exists($dest)) {
                    File::copy(Storage::disk('public')->path($document->doc_upload), $dest);
                }
            }

            if ($document->image && Storage::disk('public')->exists($document->image)) {
                $ext = pathinfo($document->image, PATHINFO_EXTENSION);
                $safeName = $this->sanitizeFileName($document->doc_name) . '_image.' . $ext;
                $dest = $categoryDir . DIRECTORY_SEPARATOR . $safeName;
                if (!File::exists($dest)) {
                    File::copy(Storage::disk('public')->path($document->image), $dest);
                }
            }
        }
    }

    private function createZip(string $tempDir, string $backupName): string
    {
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $backupName . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tempDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();

        return $zipPath;
    }

    private function generateSql(): string
    {
        $output = '';
        $output .= "-- Law Sharing Documents Backup\n";
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

    private function sanitizeDirName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $name);
    }

    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $name);
    }
}