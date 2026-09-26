<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('status');
            $table->string('receipt_reference', 100)->nullable()->after('receipt_path');
            $table->timestamp('reviewed_at')->nullable()->after('paid_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable()->after('reviewed_by');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['receipt_path', 'receipt_reference', 'reviewed_at', 'review_note']);
        });
    }
};
