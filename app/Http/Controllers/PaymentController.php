<?php

namespace App\Http\Controllers;

use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Payments/Index', [
            'subscriptions' => UserSubscription::query()
                ->with(['user:id,name,email', 'plan:id,name,price,currency,duration_days'])
                ->whereIn('status', ['pending', 'active', 'cancelled'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function approve(UserSubscription $subscription): RedirectResponse
    {
        abort_unless($subscription->status === 'pending', 422, 'Only pending payments can be approved.');

        DB::transaction(function () use ($subscription) {
            UserSubscription::where('user_id', $subscription->user_id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $subscription->plan->duration_days
                    ? now()->addDays($subscription->plan->duration_days)
                    : null,
                'cancelled_at' => null,
            ]);
        });

        return back()->with('success', 'Payment approved and subscription activated.');
    }

    public function reject(UserSubscription $subscription): RedirectResponse
    {
        abort_unless($subscription->status === 'pending', 422, 'Only pending payments can be rejected.');

        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return back()->with('success', 'Payment request rejected.');
    }
}
