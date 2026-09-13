<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Old writes could create several rows for the same user/category when
        // its permission changed. Keep the newest row before enforcing one assignment.
        $duplicateIds = DB::table('category_user')
            ->select('user_id', 'category_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('user_id', 'category_id')
            ->get()
            ->pluck('keep_id');

        DB::table('category_user')->whereNotIn('id', $duplicateIds)->delete();

        $legacyUniqueIndex = collect(Schema::getIndexes('category_user'))->first(
            fn (array $index) => $index['unique']
                && $index['columns'] === ['user_id', 'category_id', 'permission'],
        );

        Schema::table('category_user', function (Blueprint $table) use ($legacyUniqueIndex) {
            if ($legacyUniqueIndex) {
                $table->dropUnique($legacyUniqueIndex['name']);
            }
            $table->unique(['user_id', 'category_id'], 'category_user_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::table('category_user', function (Blueprint $table) {
            $table->dropUnique('category_user_assignment_unique');
            $table->unique(['user_id', 'category_id', 'permission']);
        });
    }
};
