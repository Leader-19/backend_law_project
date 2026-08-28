<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_subscription_plan', function (Blueprint $table) {
            $table->string('permission', 20)->default('view')->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('category_subscription_plan', function (Blueprint $table) {
            $table->dropColumn('permission');
        });
    }
};
