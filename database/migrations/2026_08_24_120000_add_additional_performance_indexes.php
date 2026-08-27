<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // user_subscriptions: optimize activeSubscription() and status queries
        if (! Schema::hasIndex('user_subscriptions', 'idx_user_subs_active')) {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'starts_at'], 'idx_user_subs_active');
            });
        }

        // subscription_plans: optimize is_active filtering
        if (! Schema::hasIndex('subscription_plans', 'idx_subscription_plans_active')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->index('is_active', 'idx_subscription_plans_active');
            });
        }

        // users: optimize registration_source filtering (frontend vs admin users)
        // Skip if already exists
        $userIndexes = Schema::getIndexes('users');
        $hasRegSourceIndex = collect($userIndexes)->contains(function ($index) {
            return $index['columns'] === ['registration_source'];
        });
        if (! $hasRegSourceIndex) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('registration_source', 'idx_users_registration_source');
            });
        }

        // activity_logs: optimize ordering and action filtering
        // Note: activity_logs already has composite indexes on [action, created_at]
        // Only add individual indexes if they don't exist
        $activityIndexes = Schema::getIndexes('activity_logs');
        $hasCreatedAtIndex = collect($activityIndexes)->contains(function ($index) {
            return in_array('created_at', $index['columns']);
        });
        if (! $hasCreatedAtIndex) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->index('created_at', 'idx_activity_logs_created_at');
            });
        }

        // documents: optimize ordering by created_at
        if (! Schema::hasIndex('documents', 'idx_documents_created_at')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->index('created_at', 'idx_documents_created_at');
            });
        }

        // text_contents: optimize ordering by created_at
        if (! Schema::hasIndex('text_contents', 'idx_text_contents_created_at')) {
            Schema::table('text_contents', function (Blueprint $table) {
                $table->index('created_at', 'idx_text_contents_created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_user_subs_active');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex('idx_subscription_plans_active');
        });

        // Only drop if we created it
        $userIndexes = Schema::getIndexes('users');
        if (collect($userIndexes)->has('idx_users_registration_source')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_registration_source');
            });
        }

        $activityIndexes = Schema::getIndexes('activity_logs');
        if (collect($activityIndexes)->has('idx_activity_logs_created_at')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropIndex('idx_activity_logs_created_at');
            });
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_documents_created_at');
        });

        Schema::table('text_contents', function (Blueprint $table) {
            $table->dropIndex('idx_text_contents_created_at');
        });
    }
};
