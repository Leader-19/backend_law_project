<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification is newly enforced as of this migration. Every
     * account created before today signed up (or was created by an admin)
     * under the old rules, with no way to retroactively prove ownership -
     * lock them out and support tickets stack up for nothing. Only new
     * registrations from here on go through the real verification flow.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Intentionally irreversible - we can't know which of these were
        // genuinely verified afterward vs. backfilled by this migration.
    }
};
