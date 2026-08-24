<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('category_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_contents');
    }
};
