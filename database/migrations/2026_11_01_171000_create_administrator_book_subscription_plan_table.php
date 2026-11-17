
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table linking administrator books to subscription plans.
     */
    public function up(): void
    {
        if (Schema::hasTable('administrator_book_subscription_plan')) {
            return;
        }

        Schema::create('administrator_book_subscription_plan', function (Blueprint $table) {
            $table->id();

            // Administrator book foreign key
            $table->foreignId('administrator_book_id')
                ->constrained(
                    'administrator_books',
                    'id',
                    'absp_book_fk'
                )
                ->cascadeOnDelete();

            // Subscription plan foreign key
            $table->foreignId('subscription_plan_id')
                ->constrained(
                    'subscription_plans',
                    'id',
                    'absp_plan_fk'
                )
                ->cascadeOnDelete();

            $table->timestamps();

            // Prevent duplicate book-plan assignments
            $table->unique(
                ['administrator_book_id', 'subscription_plan_id'],
                'absp_book_plan_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrator_book_subscription_plan');
    }
};
