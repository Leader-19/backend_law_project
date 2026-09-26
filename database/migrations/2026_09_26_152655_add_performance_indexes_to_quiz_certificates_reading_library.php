<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->safeIndex('quiz_attempts', ['user_id', 'quiz_id'], 'quiz_attempts_user_id_quiz_id_index');
        $this->safeIndex('quiz_attempts', ['quiz_id', 'passed'], 'quiz_attempts_quiz_id_passed_index');

        $this->safeIndex('certificates', ['certificate_number'], 'certificates_certificate_number_index');
        $this->safeIndex('certificates', ['user_id'], 'certificates_user_id_index');

        $readingTable = Schema::hasTable('reading_histories')
            ? 'reading_histories'
            : (Schema::hasTable('reading_history') ? 'reading_history' : null);

        if ($readingTable) {
            $this->safeUnique($readingTable, ['user_id', 'document_id'], "{$readingTable}_user_id_document_id_unique");
            $this->safeIndex($readingTable, ['user_id', 'last_opened_at'], "{$readingTable}_user_id_last_opened_at_index");
        }

        $this->safeUnique('user_library', ['user_id', 'document_id'], 'user_library_user_id_document_id_unique');
    }

    public function down(): void
    {
        //
    }

    private function safeIndex(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // already exists – ignore
        }
    }

    private function safeUnique(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->unique($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // already exists – ignore
        }
    }
};
