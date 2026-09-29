<?php

namespace App\Http\Controllers\Api;

use App\Models\SubscriptionPlan;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function checkout(Request $request, StripePaymentService $stripe)
    {
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:subscription_plans,id'], 'interval' => ['required', 'in:monthly,yearly']]);

        return response()->json(['checkout_url' => $stripe->checkout($request->user(), SubscriptionPlan::findOrFail($data['plan_id']), $data['interval'])]);
    }

    public function portal(Request $request, StripePaymentService $stripe)
    {
        return response()->json(['portal_url' => $stripe->portal($request->user())]);
    }
}
