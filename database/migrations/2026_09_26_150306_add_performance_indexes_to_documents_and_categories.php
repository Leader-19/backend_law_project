<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->safeIndex('documents', ['category_id'], 'documents_category_id_index');
        $this->safeIndex('documents', ['user_id'], 'documents_user_id_index');
        $this->safeIndex('documents', ['created_at'], 'documents_created_at_index');
        $this->safeIndex('documents', ['category_id', 'created_at'], 'documents_category_id_created_at_index');
        $this->safeIndex('documents', ['user_id', 'created_at'], 'documents_user_id_created_at_index');
        $this->safeIndex('documents', ['doc_name'], 'documents_doc_name_index');

        $this->safeIndex('categories', ['parent_id'], 'categories_parent_id_index');
        $this->safeIndex('categories', ['title'], 'categories_title_index');
    }

    public function down(): void
    {
        $this->safeDropIndex('documents', 'documents_category_id_index');
        $this->safeDropIndex('documents', 'documents_user_id_index');
        $this->safeDropIndex('documents', 'documents_created_at_index');
        $this->safeDropIndex('documents', 'documents_category_id_created_at_index');
        $this->safeDropIndex('documents', 'documents_user_id_created_at_index');
        $this->safeDropIndex('documents', 'documents_doc_name_index');

        $this->safeDropIndex('categories', 'categories_parent_id_index');
        $this->safeDropIndex('categories', 'categories_title_index');
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
            // Index already exists (MySQL) or not supported – ignore
        }
    }

    private function safeDropIndex(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropIndex($indexName);
            });
        } catch (Throwable $e) {
            // Index does not exist – ignore
        }
    }
};
