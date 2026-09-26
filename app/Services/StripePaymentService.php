<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Stripe\StripeClient;

class StripePaymentService
{
    private function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }

    public function checkout(User $user, SubscriptionPlan $plan, string $interval): string
    {
        abort_unless(in_array($interval, ['monthly', 'yearly'], true), 422, 'Invalid billing interval.');
        $priceId = $interval === 'yearly' ? $plan->stripe_yearly_price_id : $plan->stripe_monthly_price_id;
        abort_unless($plan->is_active && $priceId, 422, 'This plan is not configured for online payment.');

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'customer_email' => $user->email,
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'success_url' => route('billing.subscription').'?checkout=success',
            'cancel_url' => route('billing.pricing').'?checkout=cancelled',
            'metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id, 'interval' => $interval],
            'subscription_data' => ['metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id, 'interval' => $interval]],
        ]);

        return $session->url;
    }

    public function portal(User $user): string
    {
        $customer = $user->activeSubscription()->where('provider', 'stripe')->value('provider_customer_id');
        abort_unless($customer, 422, 'No Stripe subscription is available to manage.');

        return $this->client()->billingPortal->sessions->create(['customer' => $customer, 'return_url' => route('billing.subscription')])->url;
    }
}
