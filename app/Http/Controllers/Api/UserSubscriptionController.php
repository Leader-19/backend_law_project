<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubscriptionRequest;
use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $subscriptions = UserSubscription::where('user_id', $user->id)
            ->with('plan:id,name,slug,description,price,currency,duration_days,features')
            ->orderByDesc('created_at')
            ->get(['id', 'subscription_plan_id', 'status', 'starts_at', 'ends_at', 'cancelled_at', 'created_at']);

        return response()->json([
            'status' => 'success',
            'subscriptions' => $subscriptions,
        ]);
    }

    public function store(SubscriptionRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);

        if (! $plan->is_active) {
            return response()->json(['message' => 'This plan is not available.'], 422);
        }

        if ((float) $plan->price > 0) {
            $subscription = UserSubscription::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'status' => 'pending',
                ],
                [
                    'starts_at' => null,
                    'ends_at' => null,
                ],
            );

            return response()->json([
                'status' => 'pending',
                'message' => 'Your paid plan selection is pending payment confirmation.',
                'subscription' => $subscription->load('plan:id,name,slug,description,price,currency,duration_days,features'),
            ], 202);
        }

        $subscription = DB::transaction(function () use ($user, $plan) {
            $sub = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $plan->duration_days ? now()->addDays($plan->duration_days) : null,
            ]);

            foreach ($plan->categories as $category) {
                $existing = $category->users()
                    ->where('user_id', $user->id)
                    ->first();

                if (! $existing) {
                    $category->users()->attach($user->id, ['permission' => 'view']);
                }
            }

            return $sub;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription activated successfully.',
            'subscription' => $subscription->load('plan:id,name,slug,description,price,currency,duration_days,features'),
        ], 201);
    }

    public function cancel(Request $request)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->firstOrFail();

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription cancelled successfully.',
        ]);
    }
}
