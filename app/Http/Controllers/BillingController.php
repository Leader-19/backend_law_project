<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\DocumentLimitService;
use App\Services\StripePaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function pricing(Request $request): Response
    {
        return Inertia::render('Billing/Pricing', [
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('monthly_price_cents')->get(),
            'current_plan' => app(SubscriptionService::class)->currentPlanFor($request->user())->slug,
        ]);
    }

    public function subscription(Request $request): Response
    {
        $subscription = app(SubscriptionService::class)->currentSubscriptionFor($request->user());
        return Inertia::render('Billing/Subscription', [
            'subscription' => $subscription,
            'usage' => app(DocumentLimitService::class)->usage($request->user()),
            'payments' => $request->user()->payments()->with('plan:id,name')->latest()->paginate(10),
        ]);
    }

    public function checkout(Request $request, StripePaymentService $stripe): RedirectResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:subscription_plans,id'], 'interval' => ['required', 'in:monthly,yearly']]);
        $plan = SubscriptionPlan::findOrFail($data['plan_id']);
        return redirect()->away($stripe->checkout($request->user(), $plan, $data['interval']));
    }

    public function receipt(Request $request): Response
    {
        $plan = SubscriptionPlan::where('is_active', true)->findOrFail($request->integer('plan_id'));
        return Inertia::render('Billing/ReceiptPayment', [
            'plan' => $plan,
            'qr_code_url' => config('services.payments.qr_code_url'),
        ]);
    }

    public function storeReceipt(\App\Http\Requests\Api\StoreReceiptPaymentRequest $request, \App\Services\ReceiptPaymentService $payments): RedirectResponse
    {
        $data = $request->validated();
        $payments->submit($request->user(), SubscriptionPlan::findOrFail($data['plan_id']), $request->file('receipt'), $data['reference'] ?? null);
        return redirect()->route('billing.subscription')->with('success', 'Receipt sent for approval.');
    }

    public function portal(Request $request, StripePaymentService $stripe): RedirectResponse
    {
        return redirect()->away($stripe->portal($request->user()));
    }
}
