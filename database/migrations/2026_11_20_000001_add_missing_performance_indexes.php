<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // category_user: cover the unique + the "which categories does this
        // user see/own" query used by getViewableCategoryIds().
        $this->safeUnique('category_user', ['user_id', 'category_id'], 'category_user_user_id_category_id_unique');
        $this->safeIndex('category_user', ['user_id', 'category_id', 'permission'], 'category_user_user_id_category_id_permission_index');

        // category_role: cover category_id + role lookup.
        $this->safeIndex('category_role', ['category_id', 'role_id'], 'category_role_category_id_role_id_index');
        $this->safeIndex('category_role', ['role_id', 'permission'], 'category_role_role_id_permission_index');

        // reading_history: cover the "which documents has this user read" lookup.
        $this->safeIndex('reading_history', ['user_id', 'document_id'], 'reading_history_user_id_document_id_index');
        $this->safeIndex('reading_history', ['user_id', 'last_opened_at'], 'reading_history_user_id_last_opened_at_index');

        // quiz_attempts: cover the two most selective access patterns.
        $this->safeIndex('quiz_attempts', ['user_id', 'quiz_id'], 'quiz_attempts_user_id_quiz_id_index');
        $this->safeIndex('quiz_attempts', ['quiz_id', 'passed'], 'quiz_attempts_quiz_id_passed_index');

        // certificates: cover user + certificate-number lookups.
        $this->safeIndex('certificates', ['user_id'], 'certificates_user_id_index');
        $this->safeIndex('certificates', ['certificate_number'], 'certificates_certificate_number_index');

        // user_library: cover the unique + "is this doc in my library" lookup.
        $this->safeUnique('user_library', ['user_id', 'document_id'], 'user_library_user_id_document_id_unique');
        $this->safeIndex('user_library', ['user_id', 'document_id'], 'user_library_user_id_document_id_index');
    }

    public function down(): void
    {
        Schema::table('category_user', function (Blueprint $table) {
            $table->dropUnique('category_user_user_id_category_id_unique');
            $table->dropIndex('category_user_user_id_category_id_permission_index');
        });
        Schema::table('category_role', function (Blueprint $table) {
            $table->dropIndex('category_role_category_id_role_id_index');
            $table->dropIndex('category_role_role_id_permission_index');
        });
        Schema::table('reading_history', function (Blueprint $table) {
            $table->dropIndex('reading_history_user_id_document_id_index');
            $table->dropIndex('reading_history_user_id_last_opened_at_index');
        });
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_user_id_quiz_id_index');
            $table->dropIndex('quiz_attempts_quiz_id_passed_index');
        });
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropIndex('certificates_user_id_index');
            $table->dropIndex('certificates_certificate_number_index');
        });
        Schema::table('user_library', function (Blueprint $table) {
            $table->dropUnique('user_library_user_id_document_id_unique');
            $table->dropIndex('user_library_user_id_document_id_index');
        });
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
        } catch (Throwable $e) {
            // already exists
        }
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
        } catch (Throwable $e) {
            // already exists
        }
    }
};
