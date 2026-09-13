<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReceiptPaymentService
{
    public function submit(User $user, SubscriptionPlan $plan, UploadedFile $receipt, string $billingCycle, ?string $reference = null): Payment
    {
        if (! $plan->is_active || (float) $plan->price <= 0) {
            throw ValidationException::withMessages(['plan_id' => 'Select an available paid plan.']);
        }

        $path = $receipt->store('payment-receipts/'.$user->id, 'public');

        try {
            $payment = DB::transaction(function () use ($user, $plan, $path, $billingCycle, $reference) {
                $existing = Payment::query()->where('user_id', $user->id)->where('subscription_plan_id', $plan->id)
                    ->where('provider', 'qr_receipt')->where('status', 'pending')->lockForUpdate()->first();

                if ($existing) {
                    Storage::disk('public')->delete($existing->receipt_path);
                    $existing->subscription->update(['billing_interval' => $billingCycle]);
                    $existing->update(['receipt_path' => $path, 'receipt_reference' => $reference, 'amount_cents' => $this->amountCents($plan, $billingCycle)]);
                    return $existing;
                }

                $subscription = UserSubscription::create([
                    'user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'pending', 'provider' => 'qr_receipt', 'billing_interval' => $billingCycle,
                ]);

                return Payment::create([
                    'user_id' => $user->id, 'user_subscription_id' => $subscription->id, 'subscription_plan_id' => $plan->id,
                    'provider' => 'qr_receipt', 'amount_cents' => $this->amountCents($plan, $billingCycle), 'currency' => $plan->currency,
                    'status' => 'pending', 'receipt_path' => $path, 'receipt_reference' => $reference,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        app(TelegramPaymentNotifier::class)->notify($payment->fresh(['user', 'plan']));
        return $payment;
    }

    public function approve(Payment $payment, ?User $reviewer = null): Payment
    {
        return DB::transaction(function () use ($payment, $reviewer) {
            $payment = Payment::query()->with('subscription.plan.categories')->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'approved') return $payment;
            if ($payment->status !== 'pending') throw ValidationException::withMessages(['payment' => 'Only pending payments can be approved.']);

            UserSubscription::where('user_id', $payment->user_id)->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $subscription = $payment->subscription;
            $startsAt = now();
            $subscription->update(['status' => 'active', 'starts_at' => $startsAt, 'ends_at' => $this->endsAt($subscription, $startsAt), 'cancelled_at' => null]);
            // Access is derived from the active subscription by User::hasPlanCategoryAccess().
            // Do not create permanent category_user rows here: they would keep access after expiry.
            $payment->update(['status' => 'approved', 'paid_at' => now(), 'reviewed_at' => now(), 'reviewed_by' => $reviewer?->id]);
            return $payment->fresh(['user', 'plan', 'subscription']);
        });
    }

    public function reject(Payment $payment, ?User $reviewer = null, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $reviewer, $note) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'pending') throw ValidationException::withMessages(['payment' => 'Only pending payments can be rejected.']);
            $payment->subscription?->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $payment->update(['status' => 'rejected', 'reviewed_at' => now(), 'reviewed_by' => $reviewer?->id, 'review_note' => $note]);
            return $payment;
        });
    }

    private function amountCents(SubscriptionPlan $plan, string $billingCycle): int
    {
        if ($billingCycle === 'yearly') return (int) ($plan->yearly_price_cents ?? round(((float) $plan->price) * 1200));
        return (int) ($plan->monthly_price_cents ?? round(((float) $plan->price) * 100));
    }

    private function endsAt(UserSubscription $subscription, $startsAt): mixed
    {
        if (! $subscription->plan->duration_days) return null;
        return $subscription->billing_interval === 'yearly'
            ? $startsAt->copy()->addYear()
            : $startsAt->copy()->addMonth();
    }
}
