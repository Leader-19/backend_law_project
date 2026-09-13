<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSubscriptionController extends Controller
{
    /**
     * List pending payments for admin review.
     */
    public function pendingPayments(Request $request): JsonResponse
    {
        $subscriptions = UserSubscription::query()
            ->with(['user:id,name,email', 'plan:id,name,price,currency,duration_days'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'subscriptions' => $subscriptions->items(),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * List all subscriptions for admin view.
     */
    public function allSubscriptions(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = UserSubscription::query()
            ->with(['user:id,name,email', 'plan:id,name,price,currency,duration_days']);

        if ($status && in_array($status, ['pending', 'active', 'cancelled', 'expired'])) {
            $query->where('status', $status);
        }

        $subscriptions = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'subscriptions' => $subscriptions->items(),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * Approve a pending payment and activate the subscription.
     */
    public function approvePayment(UserSubscription $subscription): JsonResponse
    {
        abort_unless($subscription->status === 'pending', 422, 'Only pending payments can be approved.');

        DB::transaction(function () use ($subscription) {
            // Cancel any existing active subscription for this user
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

            // Auto-assign plan's default categories to the user
            $plan = $subscription->plan;
            foreach ($plan->categories as $category) {
                $existing = $category->users()
                    ->where('user_id', $subscription->user_id)
                    ->first();

                if (! $existing) {
                    $category->users()->attach($subscription->user_id, ['permission' => 'view']);
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => $this->actorMessage('approved', 'payment and activated subscription'),
            'subscription' => $subscription->fresh()->load(['user:id,name,email', 'plan']),
        ]);
    }

    /**
     * Reject a pending payment.
     */
    public function rejectPayment(UserSubscription $subscription): JsonResponse
    {
        abort_unless($subscription->status === 'pending', 422, 'Only pending payments can be rejected.');

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $this->actorMessage('rejected', 'payment request'),
        ]);
    }

    /**
     * List all subscription plans (including inactive).
     */
    public function listPlans(): JsonResponse
    {
        $plans = SubscriptionPlan::orderBy('price')
            ->with('categories:id,title')
            ->get()
            ->map(function ($plan) {
                $symbolMap = ['USD' => '$', 'KHR' => '៛', 'THB' => '฿'];
                $symbol = $symbolMap[$plan->currency] ?? $plan->currency;
                $formattedPrice = $plan->currency === 'KHR'
                    ? number_format($plan->price) . ' ' . $symbol
                    : $symbol . number_format($plan->price, 2);

                return array_merge($plan->toArray(), [
                    'currency_symbol' => $symbol,
                    'formatted_price' => $formattedPrice,
                ]);
            });

        return response()->json([
            'status' => 'success',
            'plans' => $plans,
        ]);
    }

    /**
     * Create a new subscription plan.
     */
    public function storePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|in:USD,KHR,THB',
            'duration_days' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'max_categories' => 'nullable|integer|min:1',
            'max_documents' => 'nullable|integer|min:1',
            'max_text_contents' => 'nullable|integer|min:1',
            'max_storage_mb' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (SubscriptionPlan::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $validated['slug'] = $slug;

        // Remove category_ids before creating the plan
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $plan = SubscriptionPlan::create($validated);

        // Attach categories if provided (permission defaults to 'view')
        if (! empty($categoryIds)) {
            $plan->categories()->sync(
                collect($categoryIds)->mapWithKeys(fn ($id) => [$id => ['permission' => 'view']])->all()
            );
        }

        SubscriptionPlan::clearCache();

        return response()->json([
            'status' => 'success',
            'message' => $this->actorMessage('created', 'plan'),
            'plan' => $plan->load('categories:id,title'),
        ], 201);
    }

    /**
     * Update a subscription plan.
     */
    public function updatePlan(Request $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|in:USD,KHR,THB',
            'duration_days' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'max_categories' => 'nullable|integer|min:1',
            'max_documents' => 'nullable|integer|min:1',
            'max_text_contents' => 'nullable|integer|min:1',
            'max_storage_mb' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        // Remove category_ids before updating the plan
        $categoryIds = $validated['category_ids'] ?? null;
        unset($validated['category_ids']);

        $subscriptionPlan->update($validated);

        // Sync categories if provided (permission defaults to 'view')
        if ($categoryIds !== null) {
            $subscriptionPlan->categories()->sync(
                collect($categoryIds)->mapWithKeys(fn ($id) => [$id => ['permission' => 'view']])->all()
            );
        }

        SubscriptionPlan::clearCache();

        return response()->json([
            'status' => 'success',
            'message' => $this->actorMessage('updated', 'plan'),
            'plan' => $subscriptionPlan->fresh()->load('categories:id,title'),
        ]);
    }

    /**
     * Delete a subscription plan.
     */
    public function destroyPlan(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $subscriptionPlan->delete();

        SubscriptionPlan::clearCache();

        return response()->json([
            'status' => 'success',
            'message' => $this->actorMessage('deleted', 'plan'),
        ]);
    }

    /**
     * Get all categories for plan assignment.
     */
    public function allCategories(): JsonResponse
    {
        $categories = Category::orderBy('title')
            ->get(['id', 'title', 'description', 'parent_id']);

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
        ]);
    }

    /**
     * Sync categories for a plan.
     */
    public function syncPlanCategories(Request $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $validated = $request->validate([
            'category_ids' => ['present', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);

        $changes = DB::transaction(function () use ($subscriptionPlan, $validated) {
            $changes = $subscriptionPlan->categories()->sync(
                collect($validated['category_ids'])
                    ->mapWithKeys(fn ($id) => [$id => ['permission' => 'view']])
                    ->all(),
            );

            SubscriptionPlan::clearCache();

            ActivityLog::record('plan_categories_synced', 'Categories updated for plan '.$subscriptionPlan->name, $subscriptionPlan, [
                'attached' => $changes['attached'],
                'detached' => $changes['detached'],
                'updated' => $changes['updated'],
            ]);

            return $changes;
        });

        return response()->json([
            'status' => 'success',
            'message' => $this->actorMessage('updated', 'plan categories'),
            'plan' => $subscriptionPlan->fresh()->load('categories:id,title'),
            'changes' => $changes,
        ]);
    }
}
