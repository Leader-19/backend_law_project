<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('monthly_price_cents')->nullable()->after('price');
            $table->unsignedInteger('yearly_price_cents')->nullable()->after('monthly_price_cents');
            $table->string('stripe_product_id')->nullable()->unique()->after('currency');
            $table->string('stripe_monthly_price_id')->nullable()->unique()->after('stripe_product_id');
            $table->string('stripe_yearly_price_id')->nullable()->unique()->after('stripe_monthly_price_id');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('status');
            $table->string('provider_subscription_id')->nullable()->unique()->after('provider');
            $table->string('provider_customer_id')->nullable()->after('provider_subscription_id');
            $table->string('billing_interval', 16)->nullable()->after('provider_customer_id');
            $table->timestamp('current_period_ends_at')->nullable()->after('ends_at');
            $table->boolean('cancel_at_period_end')->default(false)->after('current_period_ends_at');
            $table->index(['status', 'ends_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('provider_invoice_id')->nullable()->unique();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3);
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status', 'ends_at']);
            $table->dropUnique(['provider_subscription_id']);
            $table->dropColumn(['provider', 'provider_subscription_id', 'provider_customer_id', 'billing_interval', 'current_period_ends_at', 'cancel_at_period_end']);
        });
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropUnique(['stripe_product_id']);
            $table->dropUnique(['stripe_monthly_price_id']);
            $table->dropUnique(['stripe_yearly_price_id']);
            $table->dropColumn(['monthly_price_cents', 'yearly_price_cents', 'stripe_product_id', 'stripe_monthly_price_id', 'stripe_yearly_price_id']);
        });
    }
};
