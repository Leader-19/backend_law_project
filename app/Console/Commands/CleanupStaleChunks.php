<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupStaleChunks extends Command
{
    protected $signature = 'uploads:cleanup-chunks {--older-than=24 : Hours after which stale chunks are deleted}';

    protected $description = 'Remove chunked-upload temp directories that were not finalized';

    public function handle(): int
    {
        $hours = (int) $this->option('older-than');
        $chunkBase = storage_path('app/chunks');

        if (!File::isDirectory($chunkBase)) {
            $this->info('No chunks directory found — nothing to clean.');
            return self::SUCCESS;
        }

        $cutoff = now()->subHours($hours);
        $removed = 0;

        foreach (File::directories($chunkBase) as $dir) {
            $metaPath = $dir . '/meta.json';

            // Use directory modification time if meta.json is missing
            $dirTime = File::exists($metaPath)
                ? filemtime($metaPath)
                : File::lastModified($dir);

            if ($dirTime < $cutoff->timestamp) {
                File::deleteDirectory($dir);
                $removed++;
            }
        }

        $this->info("Cleaned up {$removed} stale chunk directory(ies) older than {$hours}h.");
        return self::SUCCESS;
    }
}
