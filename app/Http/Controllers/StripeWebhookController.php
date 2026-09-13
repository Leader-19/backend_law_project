<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, SubscriptionService $subscriptions)
    {
        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), (string) config('services.stripe.webhook_secret'));
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['message' => 'Invalid Stripe webhook.'], 400);
        }

        // Persisting the provider event ID makes delivery retries idempotent.
        try {
            $record = PaymentWebhookEvent::create(['provider' => 'stripe', 'event_id' => $event->id, 'event_type' => $event->type]);
        } catch (\Illuminate\Database\QueryException) {
            return response()->json(['received' => true]);
        }

        DB::transaction(function () use ($event, $subscriptions) {
            $object = $event->data->object;
            if (in_array($event->type, ['checkout.session.completed', 'customer.subscription.created', 'customer.subscription.updated'], true)) {
                $stripeId = $event->type === 'checkout.session.completed' ? $object->subscription : $object->id;
                $stripeSubscription = $event->type === 'checkout.session.completed'
                    ? (new \Stripe\StripeClient(config('services.stripe.secret')))->subscriptions->retrieve($stripeId)
                    : $object;
                $metadata = $stripeSubscription->metadata;
                $user = User::find($metadata->user_id ?? null);
                $plan = SubscriptionPlan::find($metadata->plan_id ?? null);
                if ($user && $plan && in_array($stripeSubscription->status, ['active', 'trialing'], true)) {
                    $subscriptions->activateStripeSubscription($user, $plan, $stripeSubscription->id, $stripeSubscription->customer, $metadata->interval ?? 'monthly', Carbon::createFromTimestamp($stripeSubscription->current_period_end));
                }
            }
            if ($event->type === 'customer.subscription.deleted') {
                UserSubscription::where('provider_subscription_id', $object->id)->update(['status' => 'expired', 'ends_at' => now()]);
            }
            if (in_array($event->type, ['invoice.paid', 'invoice.payment_failed'], true) && ! empty($object->subscription)) {
                $subscription = UserSubscription::where('provider_subscription_id', $object->subscription)->first();
                if ($subscription) Payment::updateOrCreate(['provider_invoice_id' => $object->id], [
                    'user_id' => $subscription->user_id, 'user_subscription_id' => $subscription->id, 'subscription_plan_id' => $subscription->subscription_plan_id,
                    'provider' => 'stripe', 'provider_payment_id' => $object->payment_intent ?? null, 'amount_cents' => $object->amount_paid ?? 0,
                    'currency' => strtoupper($object->currency), 'status' => $event->type === 'invoice.paid' ? 'paid' : 'failed',
                    'paid_at' => $event->type === 'invoice.paid' ? now() : null, 'payload' => $object->toArray(),
                ]);
            }
        });
        $record->update(['processed_at' => now()]);
        return response()->json(['received' => true]);
    }
}
