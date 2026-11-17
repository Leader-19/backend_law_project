
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Prevent duplicate table creation.
        if (Schema::hasTable('administrator_books')) {
            return;
        }

        Schema::create('administrator_books', function (Blueprint $table) {
            $table->id();

            $table->foreignId('administrator_book_category_id')
                ->nullable()
                ->constrained('administrator_book_categories')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('name')->comment('Administrator name');
            $table->text('description')->nullable();

            $table->string('file_path')
                ->nullable()
                ->comment('Uploaded file on the public disk');

            $table->timestamps();

            $table->index(
                ['administrator_book_category_id', 'created_at'],
                'administrator_books_category_created_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrator_books');
    }
};
