<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function freePlan(): SubscriptionPlan
    {
        $plan = SubscriptionPlan::updateOrCreate(['slug' => 'free'], [
            'name' => 'Free',
            'description' => 'Basic access for new users with document previews.',
            'price' => 0,
            'currency' => 'USD',
            'features' => ['5 documents per category', 'Basic category access'],
            'max_documents' => 5,
            'max_categories' => null,
            'max_text_contents' => 20,
            'max_storage_mb' => 100,
            'is_active' => true,
        ]);

        if ($plan->categories()->count() === 0) {
            $catIds = \App\Models\Category::pluck('id')->all();
            if (! empty($catIds)) {
                $plan->categories()->sync(
                    collect($catIds)->mapWithKeys(fn ($id) => [$id => ['permission' => 'view']])->all()
                );
                SubscriptionPlan::clearCache();
            }
        }

        return $plan;
    }

    public function ensureFreeSubscription(User $user): UserSubscription
    {
        $active = $user->activeSubscription()->first();
        if ($active) return $active;
        $free = $this->freePlan();
        return UserSubscription::firstOrCreate(
            ['user_id' => $user->id, 'subscription_plan_id' => $free->id, 'status' => 'active'],
            ['starts_at' => now(), 'provider' => 'internal']
        );
    }

    public function currentSubscriptionFor(User $user): UserSubscription
    {
        $this->expireEndedSubscriptions();
        return $this->ensureFreeSubscription($user)->loadMissing('plan');
    }

    public function currentPlanFor(User $user): SubscriptionPlan
    {
        return $this->currentSubscriptionFor($user)->plan;
    }

    /** Called only after a Stripe event passes signature verification. */
    public function activateStripeSubscription(User $user, SubscriptionPlan $plan, string $stripeSubscriptionId, string $customerId, string $interval, \DateTimeInterface $periodEnd): UserSubscription
    {
        return DB::transaction(function () use ($user, $plan, $stripeSubscriptionId, $customerId, $interval, $periodEnd) {
            UserSubscription::where('user_id', $user->id)->where('status', 'active')->update([
                'status' => 'cancelled', 'cancelled_at' => now(),
            ]);
            return UserSubscription::updateOrCreate(['provider_subscription_id' => $stripeSubscriptionId], [
                'user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'active',
                'provider' => 'stripe', 'provider_customer_id' => $customerId, 'billing_interval' => $interval,
                'starts_at' => now(), 'ends_at' => $periodEnd, 'current_period_ends_at' => $periodEnd,
                'cancelled_at' => null, 'cancel_at_period_end' => false,
            ]);
        });
    }

    public function expireEndedSubscriptions(): int
    {
        return UserSubscription::where('status', 'active')->whereNotNull('ends_at')->where('ends_at', '<=', now())
            ->update(['status' => 'expired']);
    }
}
