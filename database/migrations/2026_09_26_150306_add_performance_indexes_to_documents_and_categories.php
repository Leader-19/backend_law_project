<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Helper to check if an index already exists
        $hasIndex = function (string $table, string $indexName): bool {
            $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->unique()
                ->toArray();

            return in_array($indexName, $indexes, true);
        };

        Schema::table('documents', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('documents', 'documents_category_id_index')) {
                $table->index('category_id');
            }

            if (!$hasIndex('documents', 'documents_user_id_index')) {
                $table->index('user_id');
            }

            if (!$hasIndex('documents', 'documents_created_at_index')) {
                $table->index('created_at');
            }

            if (!$hasIndex('documents', 'documents_category_id_created_at_index')) {
                $table->index(['category_id', 'created_at']);
            }

            if (!$hasIndex('documents', 'documents_user_id_created_at_index')) {
                $table->index(['user_id', 'created_at']);
            }

            if (!$hasIndex('documents', 'documents_doc_name_index')) {
                $table->index('doc_name');
            }
        });

        Schema::table('categories', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('categories', 'categories_parent_id_index')) {
                $table->index('parent_id');
            }

            if (!$hasIndex('categories', 'categories_title_index')) {
                $table->index('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['category_id', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['doc_name']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['title']);
        });
    }
};
