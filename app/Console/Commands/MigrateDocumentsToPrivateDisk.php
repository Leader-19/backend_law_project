<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateDocumentsToPrivateDisk extends Command
{
    protected $signature = 'documents:migrate-to-private {--delete-public : Remove the original public-disk copy once moved}';

    protected $description = 'Move previously-uploaded documents off the public disk, where they were reachable by anyone who had the URL regardless of category/subscription access';

    public function handle(): int
    {
        $moved = 0;
        $skipped = 0;
        $missing = 0;
        $cleaned = 0;

        Document::query()->whereNotNull('doc_upload')->get()->each(function (Document $document) use (&$moved, &$skipped, &$missing, &$cleaned) {
            $publicPath = storage_path('app/public/'.$document->doc_upload);
            $privatePath = storage_path('app/private/'.$document->doc_upload);

            if (File::exists($privatePath)) {
                $skipped++;

                // Already migrated on a previous run - still honor --delete-public
                // so a leftover public copy from that run gets cleaned up now.
                if ($this->option('delete-public') && File::exists($publicPath)) {
                    File::delete($publicPath);
                    $cleaned++;
                }

                return;
            }

            if (! File::exists($publicPath)) {
                $this->warn("Missing file for document #{$document->id}: {$document->doc_upload}");
                $missing++;

                return;
            }

            File::makeDirectory(dirname($privatePath), 0755, true, true);
            File::copy($publicPath, $privatePath);

            if ($this->option('delete-public')) {
                File::delete($publicPath);
            }

            $moved++;
        });

        $this->info("Migrated {$moved} document(s) to the private disk.");
        $this->info("Skipped {$skipped} already-private document(s).");

        if ($cleaned > 0) {
            $this->info("Deleted {$cleaned} leftover public-disk copy(ies) from a previous run.");
        }

        if ($missing > 0) {
            $this->warn("{$missing} document(s) had no file on either disk — check them manually.");
        }

        if (! $this->option('delete-public')) {
            $this->warn('Public-disk copies were left in place. Re-run with --delete-public once you have verified the migration, to close the public-URL exposure.');
        }

        return self::SUCCESS;
    }
}
