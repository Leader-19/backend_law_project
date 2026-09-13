<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function download(Request $request): StreamedResponse
    {
        $request->validate([
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:categories,id',
            'include_database' => 'sometimes|boolean',
        ]);

        $categoryIds = $request->input('category_ids');
        $includeDatabase = $request->boolean('include_database');

        $backupName = 'backup_'.now()->format('Y-m-d_H-i-s').'_'.Str::lower(Str::random(6));
        $tempDir = storage_path('app/private/backups/'.$backupName);
        $zipPath = null;
        File::makeDirectory($tempDir, 0700, true, true);

        try {
            $expandedCategoryIds = $this->expandCategoryIds($categoryIds);
            $documents = Document::whereIn('category_id', $expandedCategoryIds)
                ->with('category:id,title')
                ->get();

            if ($includeDatabase) {
                $this->exportDatabase($tempDir);
            }
            $export = $this->exportFiles($tempDir, $documents);
            $this->writeCategoryMetadata($tempDir, $expandedCategoryIds, $documents);
            $this->writeManifest($tempDir, $categoryIds, $expandedCategoryIds, $documents, $export['missing_files'], $includeDatabase);
            $zipPath = $this->createZip($tempDir, $backupName);

            ActivityLog::record('backup_created', 'Backup archive created', null, [
                'requested_category_ids' => $categoryIds,
                'includes_database' => $includeDatabase,
                'document_count' => $documents->count(),
                'file_count' => $export['file_count'],
                'missing_file_count' => count($export['missing_files']),
            ]);
        } catch (\Throwable $exception) {
            File::deleteDirectory($tempDir);
            if ($zipPath !== null) {
                File::delete($zipPath);
            }
            throw $exception;
        }

        return response()->streamDownload(function () use ($zipPath, $tempDir) {
            try {
                readfile($zipPath);
            } finally {
                File::deleteDirectory($tempDir);
                File::delete($zipPath);
            }
        }, $backupName.'.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    private function exportDatabase(string $tempDir): void
    {
        $handle = fopen($tempDir.DIRECTORY_SEPARATOR.'database.sql', 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to create the database backup file.');
        }

        try {
            fwrite($handle, "-- SPRITUP database backup\n-- Generated: ".now()->format('Y-m-d H:i:s T')."\n\n");
            foreach ($this->databaseTables() as $tableName) {
                $quotedTable = $this->quoteIdentifier($tableName);
                $createSql = $this->createTableStatement($tableName);
                fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n{$createSql};\n\n");

                $columns = [];
                $valueRows = [];
                foreach (DB::table($tableName)->cursor() as $row) {
                    if ($columns === []) {
                        $columns = array_map($this->quoteIdentifier(...), array_keys((array) $row));
                    }
                    $valueRows[] = '('.implode(', ', array_map(
                        fn ($value) => $value === null ? 'NULL' : DB::getPdo()->quote((string) $value),
                        array_values((array) $row),
                    )).')';

                    if (count($valueRows) === 500) {
                        fwrite($handle, "INSERT INTO {$quotedTable} (".implode(', ', $columns).') VALUES '.implode(', ', $valueRows).";\n");
                        $valueRows = [];
                    }
                }
                if ($valueRows !== []) {
                    fwrite($handle, "INSERT INTO {$quotedTable} (".implode(', ', $columns).') VALUES '.implode(', ', $valueRows).";\n");
                }
                fwrite($handle, "\n");
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return list<string> */
    private function databaseTables(): array
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => array_map(
                fn (object $table) => (string) array_values((array) $table)[0],
                DB::select('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\''),
            ),
            'sqlite' => array_map(
                fn (object $table) => $table->name,
                DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"),
            ),
            default => throw new \RuntimeException('Full database backup currently supports MySQL/MariaDB and SQLite. Use your database provider\'s native backup tool for '.DB::getDriverName().'.'),
        };
    }

    private function createTableStatement(string $tableName): string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => (function () use ($tableName): string {
                $create = DB::select('SHOW CREATE TABLE '.$this->quoteIdentifier($tableName));
                return (string) (array_values((array) $create[0])[1] ?? '');
            })(),
            'sqlite' => (string) (DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$tableName])->sql ?? ''),
            default => throw new \LogicException('Unsupported database driver.'),
        };
    }

    private function quoteIdentifier(string $identifier): string
    {
        $quote = DB::getDriverName() === 'sqlite' ? '"' : '`';

        return $quote.str_replace($quote, $quote.$quote, $identifier).$quote;
    }

    private function exportFiles(string $tempDir, $documents): array
    {
        $categoriesDir = $tempDir.DIRECTORY_SEPARATOR.'categories';
        File::makeDirectory($categoriesDir, 0700, true, true);
        $fileCount = 0;
        $missingFiles = [];

        foreach ($documents as $document) {
            $categoryName = $document->category?->title ?? 'uncategorized';
            $categoryDir = $categoriesDir.DIRECTORY_SEPARATOR.$this->sanitizeDirName($categoryName);

            if (! File::isDirectory($categoryDir)) {
                File::makeDirectory($categoryDir, 0755, true, true);
            }

            if ($document->doc_upload && Storage::disk('public')->exists($document->doc_upload)) {
                $ext = pathinfo($document->doc_upload, PATHINFO_EXTENSION);
                $safeName = $document->id.'_'. $this->sanitizeFileName($document->doc_name).'.'.$ext;
                $dest = $categoryDir.DIRECTORY_SEPARATOR.$safeName;
                if (! File::exists($dest)) {
                    File::copy(Storage::disk('public')->path($document->doc_upload), $dest);
                    $fileCount++;
                }
            } elseif ($document->doc_upload) {
                $missingFiles[] = $document->doc_upload;
            }

            if ($document->image && Storage::disk('public')->exists($document->image)) {
                $ext = pathinfo($document->image, PATHINFO_EXTENSION);
                $safeName = $document->id.'_'. $this->sanitizeFileName($document->doc_name).'_image.'.$ext;
                $dest = $categoryDir.DIRECTORY_SEPARATOR.$safeName;
                if (! File::exists($dest)) {
                    File::copy(Storage::disk('public')->path($document->image), $dest);
                    $fileCount++;
                }
            } elseif ($document->image) {
                $missingFiles[] = $document->image;
            }
        }

        return ['file_count' => $fileCount, 'missing_files' => $missingFiles];
    }

    private function createZip(string $tempDir, string $backupName): string
    {
        $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$backupName.'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create the backup archive.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tempDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        if (! $zip->close()) {
            throw new \RuntimeException('Unable to finalize the backup archive.');
        }

        return $zipPath;
    }

    private function expandCategoryIds(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $ids = collect($categoryIds)->map(fn ($id) => (int) $id)->unique()->values();
        $pending = $ids->all();
        while ($pending !== []) {
            $pending = Category::whereIn('parent_id', $pending)->pluck('id')->all();
            $newIds = collect($pending)->diff($ids)->values();
            $ids = $ids->merge($newIds)->unique()->values();
            $pending = $newIds->all();
        }

        return $ids->all();
    }

    private function writeCategoryMetadata(string $tempDir, array $categoryIds, $documents): void
    {
        $categories = Category::whereIn('id', $categoryIds)
            ->get(['id', 'parent_id', 'title', 'description', 'created_at', 'updated_at']);

        file_put_contents($tempDir.DIRECTORY_SEPARATOR.'category-data.json', json_encode([
            'categories' => $categories,
            'documents' => $documents->map(fn (Document $document) => $document->only([
                'id', 'category_id', 'doc_name', 'doc_title', 'doc_upload', 'image', 'description', 'created_at', 'updated_at',
            ]))->values(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function writeManifest(string $tempDir, array $requestedCategoryIds, array $expandedCategoryIds, $documents, array $missingFiles, bool $includesDatabase): void
    {
        file_put_contents($tempDir.DIRECTORY_SEPARATOR.'manifest.json', json_encode([
            'created_at' => now()->toIso8601String(),
            'requested_category_ids' => $requestedCategoryIds,
            'included_category_ids' => $expandedCategoryIds,
            'includes_database' => $includesDatabase,
            'document_count' => $documents->count(),
            'missing_files' => $missingFiles,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function sanitizeDirName(string $name): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $name);
        return mb_substr($safe, 0, 80);
    }

    private function sanitizeFileName(string $name): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $name);
        return mb_substr($safe, 0, 80);
    }
}
