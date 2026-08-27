<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_subscription_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Short custom name to stay under MySQL’s 64-char limit
            $table->unique(
                ['subscription_plan_id', 'category_id'],
                'cat_sub_plan_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_subscription_plan');
    }
};
