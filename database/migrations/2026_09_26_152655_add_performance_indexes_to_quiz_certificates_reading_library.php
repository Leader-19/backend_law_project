<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasIndex = function (string $table, string $indexName): bool {
            if (! Schema::hasTable($table)) {
                return true; // skip if table doesn't exist
            }

            $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->unique()
                ->toArray();

            return in_array($indexName, $indexes, true);
        };

        // quiz_attempts
        if (Schema::hasTable('quiz_attempts')) {
            Schema::table('quiz_attempts', function (Blueprint $table) use ($hasIndex) {
                if (! $hasIndex('quiz_attempts', 'quiz_attempts_user_id_quiz_id_index')) {
                    $table->index(['user_id', 'quiz_id']);
                }
                if (! $hasIndex('quiz_attempts', 'quiz_attempts_quiz_id_passed_index')) {
                    $table->index(['quiz_id', 'passed']);
                }
            });
        }

        // certificates
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) use ($hasIndex) {
                if (! $hasIndex('certificates', 'certificates_certificate_number_index')) {
                    $table->index('certificate_number');
                }
                if (! $hasIndex('certificates', 'certificates_user_id_index')) {
                    $table->index('user_id');
                }
            });
        }

        // reading history – try both possible names
        $readingTable = Schema::hasTable('reading_histories')
            ? 'reading_histories'
            : (Schema::hasTable('reading_history') ? 'reading_history' : null);

        if ($readingTable) {
            Schema::table($readingTable, function (Blueprint $table) use ($hasIndex, $readingTable) {
                if (! $hasIndex($readingTable, "{$readingTable}_user_id_document_id_unique")) {
                    $table->unique(['user_id', 'document_id']);
                }
                if (! $hasIndex($readingTable, "{$readingTable}_user_id_last_opened_at_index")) {
                    $table->index(['user_id', 'last_opened_at']);
                }
            });
        }

        // user_library
        if (Schema::hasTable('user_library')) {
            Schema::table('user_library', function (Blueprint $table) use ($hasIndex) {
                if (! $hasIndex('user_library', 'user_library_user_id_document_id_unique')) {
                    $table->unique(['user_id', 'document_id']);
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};
