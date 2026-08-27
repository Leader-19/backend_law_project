<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_user', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['category_id']);
            $table->dropUnique(['user_id', 'category_id', 'permission']);
            $table->string('permission')->change();
            $table->unique(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('category_user', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'category_id']);
            $table->enum('permission', ['view', 'create', 'edit', 'delete', 'manage'])->change();
            $table->unique(['user_id', 'category_id', 'permission']);
        });
    }
};
