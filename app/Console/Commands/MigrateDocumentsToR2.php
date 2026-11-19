<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateDocumentsToR2 extends Command
{
    protected $signature = 'documents:migrate-to-r2 {--delete-local : Remove the local copy once confirmed uploaded to R2}';

    protected $description = 'Copy existing documents and images from local disk (private or public) to Cloudflare R2';

    private int $migrated = 0;

    private int $skipped = 0;

    private int $missing = 0;

    private int $failed = 0;

    public function handle(): int
    {
        Document::query()->get()->each(function (Document $document) {
            if ($document->doc_upload) {
                $this->migrateFile($document, 'doc_upload', $document->doc_upload);
            }
            if ($document->image) {
                $this->migrateFile($document, 'image', $document->image);
            }
        });

        $this->info("Migrated {$this->migrated} file(s) to R2.");
        $this->info("Skipped {$this->skipped} already-on-R2 file(s).");

        if ($this->failed > 0) {
            $this->error("{$this->failed} file(s) failed to upload - check the messages above.");
        }

        if ($this->missing > 0) {
            $this->warn("{$this->missing} file(s) had no local copy on either disk - check them manually.");
        }

        if (! $this->option('delete-local')) {
            $this->warn('Local copies were left in place. Re-run with --delete-local once you have verified the migration.');
        }

        return self::SUCCESS;
    }

    private function migrateFile(Document $document, string $column, string $relativePath): void
    {
        $s3 = Storage::disk('s3');

        if ($s3->exists($relativePath)) {
            $this->skipped++;

            if ($this->option('delete-local')) {
                $this->deleteLocalCopy($relativePath);
            }

            return;
        }

        $localPath = $this->findLocalPath($relativePath);

        if (! $localPath) {
            $this->warn("Missing file for document #{$document->id} ({$column}): {$relativePath}");
            $this->missing++;

            return;
        }

        $stream = fopen($localPath, 'rb');
        $uploaded = $stream !== false && $s3->put($relativePath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        if (! $uploaded) {
            $this->error("Failed to upload document #{$document->id} ({$column}): {$relativePath}");
            $this->failed++;

            return;
        }

        $this->migrated++;

        if ($this->option('delete-local')) {
            $this->deleteLocalCopy($relativePath);
        }
    }

    private function findLocalPath(string $relativePath): ?string
    {
        $privatePath = storage_path('app/private/'.$relativePath);
        if (file_exists($privatePath)) {
            return $privatePath;
        }

        $publicPath = storage_path('app/public/'.$relativePath);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        return null;
    }

    private function deleteLocalCopy(string $relativePath): void
    {
        foreach (['app/private/'.$relativePath, 'app/public/'.$relativePath] as $rel) {
            $path = storage_path($rel);
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
