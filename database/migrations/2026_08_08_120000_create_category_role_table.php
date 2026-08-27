<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->enum('permission', ['view', 'create', 'edit', 'delete', 'manage']);
            $table->timestamps();
            $table->unique(['category_id', 'role_id']);
            $table->index(['role_id', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_role');
    }
};
