<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Category;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SubscriptionPlanWebController extends Controller
{
    public function index(Request $request)
    {
        $plans = SubscriptionPlan::with('categories:id,title')->orderBy('price')->get();
        $categories = Category::orderBy('title')->get(['id', 'title', 'parent_id']);

        // Filter subscriptions by status if provided
        $statusFilter = $request->query('status');
        $subscriptionQuery = UserSubscription::with(['user', 'plan'])
            ->when($statusFilter && in_array($statusFilter, ['active', 'pending', 'cancelled', 'expired']),
                fn ($q) => $q->where('status', $statusFilter))
            ->latest();
        $subscriptions = $subscriptionQuery->paginate(15)->withQueryString();

        // Multi-plan stats: count users with multiple active subscriptions
        $activeSubCounts = UserSubscription::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->selectRaw('user_id, count(*) as plan_count')
            ->groupBy('user_id')
            ->pluck('plan_count', 'user_id');

        $totalActiveSubscriptions = $activeSubCounts->sum();
        $multiPlanUserCount = $activeSubCounts->filter(fn ($count) => $count > 1)->count();

        return Inertia::render('SubscriptionPlans/Index', [
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'categories' => $categories,
            'subscriptionStats' => [
                'total_active' => $totalActiveSubscriptions,
                'multi_plan_users' => $multiPlanUserCount,
                'active_counts_by_user' => $activeSubCounts->toArray(),
            ],
            'currencies' => [
                ['code' => 'USD', 'symbol' => '$', 'name' => 'USD ($)'],
                ['code' => 'KHR', 'symbol' => '៛', 'name' => 'KHR (៛)'],
                ['code' => 'THB', 'symbol' => '฿', 'name' => 'THB (฿)'],
            ],
        ]);
    }

    public function store(Request $request)
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

        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $plan = SubscriptionPlan::create($validated);

        if (! empty($categoryIds)) {
            $plan->categories()->sync(
                collect($categoryIds)->mapWithKeys(fn ($id) => [$id => ['permission' => 'view']])->all()
            );
        }

        SubscriptionPlan::clearCache();

        return redirect()->back()->with('success', 'Subscription plan created successfully.');
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
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

        $categoryIds = $validated['category_ids'] ?? null;
        unset($validated['category_ids']);

        $subscriptionPlan->update($validated);

        if ($categoryIds !== null) {
            $subscriptionPlan->categories()->sync(
                collect($categoryIds)->mapWithKeys(fn ($id) => [$id => ['permission' => 'view']])->all()
            );
        }

        SubscriptionPlan::clearCache();

        return redirect()->back()->with('success', 'Subscription plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();

        return redirect()->back()->with('success', 'Subscription plan deleted successfully.');
    }

    public function categories(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 5), 100);
        $search = trim((string) $request->query('search', ''));

        $query = Category::orderBy('title');

        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        $paginated = $query->paginate($perPage)->withQueryString();

        return Inertia::render('SubscriptionPlans/Categories', [
            'plan' => $subscriptionPlan->load('categories:id,title,parent_id'),
            'categories' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'filters' => ['search' => $search],
        ]);
    }

    public function syncCategories(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $validated = $request->validate([
            'category_ids' => ['present', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);

        $subscriptionPlan->categories()->sync(
            collect($validated['category_ids'])->mapWithKeys(fn ($id) => [$id => ['permission' => 'view']])->all()
        );

        SubscriptionPlan::clearCache();

        return back()->with('success', 'Plan categories updated successfully.');
    }
}
