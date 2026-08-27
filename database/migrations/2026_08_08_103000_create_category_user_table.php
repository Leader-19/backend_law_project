<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->enum('permission', ['view', 'create', 'edit', 'delete', 'manage']);
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'permission']);
            $table->index(['category_id', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_user');
    }
};
