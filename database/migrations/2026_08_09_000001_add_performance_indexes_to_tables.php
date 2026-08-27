<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('parent_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('user_id');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('subject_id');
            $table->index('subject_type');
            $table->index('causer_id');
            $table->index('causer_type');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['parent_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['subject_id']);
            $table->dropIndex(['subject_type']);
            $table->dropIndex(['causer_id']);
            $table->dropIndex(['causer_type']);
            $table->dropIndex(['ip_address']);
        });
    }
};
