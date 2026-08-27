<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->integer('last_page')->default(1);
            $table->integer('total_pages')->default(1);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_history');
    }
};
