<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->dropColumn(['guest_name', 'guest_email']);
        });
    }
};
