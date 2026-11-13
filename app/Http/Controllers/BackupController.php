<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
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
        $this->authorize('viewAny', Category::class); // adjust policy if needed

        // Only root categories + direct children (lighter than full recursive)
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with(['children:id,parent_id,title'])
            ->orderBy('title')
            ->get(['id', 'title', 'parent_id']);

        return Inertia::render('Backup/BackupIndex', [
            'categories' => $categories,
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Category::class);

        $validated = $request->validate([
            'category_ids' => ['required', 'array', 'min:1', 'max:100'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'include_database' => ['sometimes', 'boolean'],
        ]);

        $categoryIds = $validated['category_ids'];
        $includeDatabase = $request->boolean('include_database');

        // Prevent timeout on large backups
        set_time_limit(0);

        $backupName = 'backup_'.now()->format('Y-m-d_H-i-s').'_'.Str::lower(Str::random(6));
        $tempDir = storage_path('app/private/backups/'.$backupName);
        $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$backupName.'.zip';
        File::makeDirectory($tempDir, 0700, true, true);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            File::deleteDirectory($tempDir);
            throw new \RuntimeException('Unable to create the backup archive.');
        }
        $metadataHandle = null;
        $zipClosed = false;

        try {
            $expandedCategoryIds = $this->expandCategoryIds($categoryIds);

            // Stream documents in chunks instead of loading all at once
            $documentCount = 0;
            $export = ['file_count' => 0, 'missing_file_count' => 0, 'missing_files' => []];
            $metadataPath = $tempDir.DIRECTORY_SEPARATOR.'category-data.json';
            $metadataHandle = fopen($metadataPath, 'wb');
            if ($metadataHandle === false) {
                throw new \RuntimeException('Unable to create category metadata.');
            }
            $categories = Category::whereIn('id', $expandedCategoryIds)
                ->get(['id', 'parent_id', 'title', 'description', 'created_at', 'updated_at']);
            fwrite($metadataHandle, '{"categories":'.json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).',"documents":[');
            $isFirstMetadata = true;

            Document::query()
                ->whereIn('category_id', $expandedCategoryIds)
                ->with('category:id,title')
                ->select([
                    'id',
                    'category_id',
                    'doc_name',
                    'doc_title',
                    'doc_upload',
                    'image',
                    'description',
                    'created_at',
                    'updated_at',
                ])
                ->orderBy('id')
                ->chunkById(200, function ($documents) use ($zip, $metadataHandle, &$isFirstMetadata, &$export, &$documentCount) {
                    $result = $this->exportFiles($zip, $documents);
                    $export['file_count'] += $result['file_count'];
                    $export['missing_file_count'] += $result['missing_file_count'];
                    $remainingSamples = max(0, 1000 - count($export['missing_files']));
                    if ($remainingSamples > 0) {
                        $export['missing_files'] = array_merge(
                            $export['missing_files'],
                            array_slice($result['missing_files'], 0, $remainingSamples)
                        );
                    }

                    foreach ($documents as $document) {
                        $metadata = json_encode($document->only([
                            'id',
                            'category_id',
                            'doc_name',
                            'doc_title',
                            'doc_upload',
                            'image',
                            'description',
                            'created_at',
                            'updated_at',
                        ]), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                        fwrite($metadataHandle, ($isFirstMetadata ? '' : ',').$metadata);
                        $isFirstMetadata = false;
                    }

                    $documentCount += $documents->count();
                });

            fwrite($metadataHandle, "]}\n");
            fclose($metadataHandle);
            $metadataHandle = null;

            if ($includeDatabase) {
                $this->exportDatabase($tempDir);
                if (! $zip->addFile($tempDir.DIRECTORY_SEPARATOR.'database.sql', 'database.sql')) {
                    throw new \RuntimeException('Unable to add the database dump to the backup archive.');
                }
            }

            $this->writeManifest(
                $tempDir,
                $categoryIds,
                $expandedCategoryIds,
                $documentCount,
                $export['missing_files'],
                $export['missing_file_count'],
                $includeDatabase
            );

            if (! $zip->addFile($metadataPath, 'category-data.json')
                || ! $zip->addFile($tempDir.DIRECTORY_SEPARATOR.'manifest.json', 'manifest.json')) {
                throw new \RuntimeException('Unable to add backup metadata to the archive.');
            }
            if (! $zip->close()) {
                throw new \RuntimeException('Unable to finalize the backup archive.');
            }
            $zipClosed = true;

            ActivityLog::record('backup_created', 'Backup archive created', null, [
                'requested_category_ids' => $categoryIds,
                'includes_database' => $includeDatabase,
                'document_count' => $documentCount,
                'file_count' => $export['file_count'],
                'missing_file_count' => $export['missing_file_count'],
                'missing_file_samples_truncated' => $export['missing_file_count'] > count($export['missing_files']),
            ]);
        } catch (\Throwable $exception) {
            if (is_resource($metadataHandle)) {
                fclose($metadataHandle);
            }
            if (! $zipClosed) {
                $zip->close();
            }
            File::deleteDirectory($tempDir);
            if ($zipPath !== null && File::exists($zipPath)) {
                File::delete($zipPath);
            }
            throw $exception;
        }

        return response()->streamDownload(function () use ($zipPath, $tempDir) {
            $handle = false;
            try {
                $handle = fopen($zipPath, 'rb');
                if ($handle === false) {
                    throw new \RuntimeException('Unable to read the completed backup archive.');
                }
                while (! feof($handle)) {
                    $chunk = fread($handle, 1024 * 1024); // 1MB chunks
                    if ($chunk === false) {
                        throw new \RuntimeException('Failed while streaming the backup archive.');
                    }
                    echo $chunk;
                    flush();
                }
            } finally {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                File::deleteDirectory($tempDir);
                File::delete($zipPath);
            }
        }, $backupName.'.zip', [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string) filesize($zipPath),
            'Cache-Control' => 'private, no-store',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function exportDatabase(string $tempDir): void
    {
        $handle = fopen($tempDir.DIRECTORY_SEPARATOR.'database.sql', 'wb');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create the database backup file.');
        }

        try {
            fwrite($handle, "-- SPRITUP database backup\n");
            fwrite($handle, '-- Generated: '.now()->format('Y-m-d H:i:s T')."\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($this->databaseTables() as $tableName) {
                $quotedTable = $this->quoteIdentifier($tableName);
                $createSql = $this->createTableStatement($tableName);

                fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
                fwrite($handle, "{$createSql};\n\n");

                $columns = [];
                $valueRows = [];

                // cursor() already streams rows – good
                foreach (DB::table($tableName)->cursor() as $row) {
                    $rowArray = (array) $row;

                    if ($columns === []) {
                        $columns = array_map($this->quoteIdentifier(...), array_keys($rowArray));
                    }

                    $valueRows[] = '('.implode(', ', array_map(
                        fn ($value) => $value === null ? 'NULL' : DB::getPdo()->quote((string) $value),
                        array_values($rowArray)
                    )).')';

                    if (count($valueRows) >= 200) {
                        fwrite(
                            $handle,
                            "INSERT INTO {$quotedTable} (".implode(', ', $columns).') VALUES '.
                            implode(",\n", $valueRows).";\n"
                        );
                        $valueRows = [];
                    }
                }

                if ($valueRows !== []) {
                    fwrite(
                        $handle,
                        "INSERT INTO {$quotedTable} (".implode(', ', $columns).') VALUES '.
                        implode(",\n", $valueRows).";\n"
                    );
                }

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
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
                DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
            ),
            'sqlite' => array_map(
                fn (object $table) => $table->name,
                DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
            ),
            default => throw new \RuntimeException(
                'Full database backup currently supports MySQL/MariaDB and SQLite. Use your database provider\'s native backup tool for '.DB::getDriverName().'.'
            ),
        };
    }

    private function createTableStatement(string $tableName): string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => (function () use ($tableName): string {
                $create = DB::select('SHOW CREATE TABLE '.$this->quoteIdentifier($tableName));

                return (string) (array_values((array) $create[0])[1] ?? '');
            })(),
            'sqlite' => (string) (DB::selectOne(
                "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$tableName]
            )->sql ?? ''),
            default => throw new \LogicException('Unsupported database driver.'),
        };
    }

    private function quoteIdentifier(string $identifier): string
    {
        $quote = DB::getDriverName() === 'sqlite' ? '"' : '`';

        return $quote.str_replace($quote, $quote.$quote, $identifier).$quote;
    }

    private function exportFiles(ZipArchive $zip, $documents): array
    {
        $fileCount = 0;
        $missingFiles = [];
        $disk = Storage::disk('public');

        foreach ($documents as $document) {
            $categoryName = $document->category?->title ?? 'uncategorized';
            $categoryDir = 'categories/'.$document->category_id.'_'.$this->sanitizeDirName($categoryName);

            // Main document file
            if ($document->doc_upload) {
                if ($disk->exists($document->doc_upload)) {
                    $ext = pathinfo($document->doc_upload, PATHINFO_EXTENSION);
                    $safeName = $document->id.'_'.$this->sanitizeFileName($document->doc_name).'.'.$ext;
                    if (! $zip->addFile($disk->path($document->doc_upload), $categoryDir.'/'.$safeName)) {
                        throw new \RuntimeException("Unable to add document {$document->id} to the backup archive.");
                    }
                    $fileCount++;
                } else {
                    $missingFiles[] = $document->doc_upload;
                }
            }

            // Image file
            if ($document->image) {
                if ($disk->exists($document->image)) {
                    $ext = pathinfo($document->image, PATHINFO_EXTENSION);
                    $safeName = $document->id.'_'.$this->sanitizeFileName($document->doc_name).'_image.'.$ext;
                    if (! $zip->addFile($disk->path($document->image), $categoryDir.'/'.$safeName)) {
                        throw new \RuntimeException("Unable to add the image for document {$document->id} to the backup archive.");
                    }
                    $fileCount++;
                } else {
                    $missingFiles[] = $document->image;
                }
            }
        }

        return [
            'file_count' => $fileCount,
            'missing_file_count' => count($missingFiles),
            'missing_files' => $missingFiles,
        ];
    }

    private function expandCategoryIds(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $ids = collect($categoryIds)->map(fn ($id) => (int) $id)->unique()->values();
        $pending = $ids->all();

        while ($pending !== []) {
            $children = Category::whereIn('parent_id', $pending)->pluck('id');
            $newIds = $children->diff($ids)->values();

            if ($newIds->isEmpty()) {
                break;
            }

            $ids = $ids->merge($newIds)->unique()->values();
            $pending = $newIds->all();
        }

        return $ids->all();
    }

    private function writeManifest(
        string $tempDir,
        array $requestedCategoryIds,
        array $expandedCategoryIds,
        int $documentCount,
        array $missingFiles,
        int $missingFileCount,
        bool $includesDatabase
    ): void {
        file_put_contents(
            $tempDir.DIRECTORY_SEPARATOR.'manifest.json',
            json_encode([
                'created_at' => now()->toIso8601String(),
                'requested_category_ids' => $requestedCategoryIds,
                'included_category_ids' => $expandedCategoryIds,
                'includes_database' => $includesDatabase,
                'document_count' => $documentCount,
                'missing_file_count' => $missingFileCount,
                'missing_files' => $missingFiles,
                'missing_file_samples_truncated' => $missingFileCount > count($missingFiles),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
    }

    private function sanitizeDirName(string $name): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $name) ?? 'category';

        return mb_substr(trim($safe), 0, 80) ?: 'category';
    }

    private function sanitizeFileName(string $name): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $name) ?? 'file';

        return mb_substr(trim($safe), 0, 80) ?: 'file';
    }
}
