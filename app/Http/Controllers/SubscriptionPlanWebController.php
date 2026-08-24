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
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('price')->get();
        $subscriptions = UserSubscription::with(['user', 'plan'])->latest()->paginate(15);

        return Inertia::render('SubscriptionPlans/Index', [
            'plans' => $plans,
            'subscriptions' => $subscriptions,
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
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (SubscriptionPlan::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $validated['slug'] = $slug;

        SubscriptionPlan::create($validated);

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
        ]);

        $subscriptionPlan->update($validated);

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

        $subscriptionPlan->categories()->sync($validated['category_ids']);

        return back()->with('success', 'Plan categories updated successfully.');
    }
}
