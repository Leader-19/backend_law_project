<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FrontendUserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $perPage = min(max((int) $request->integer('per_page', 10), 5), 100);
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->where('registration_source', 'frontend')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->with('roles')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar ? asset(Storage::url($user->avatar)) : null,
                    'roles' => $user->roles->pluck('name'),
                    'created_at' => $user->created_at?->diffForHumans(),
                ];
            });

        return Inertia::render('Users/FrontendIndex', [
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'filters' => ['search' => $search, 'per_page' => $perPage],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $user = User::where('registration_source', 'frontend')
            ->with(['roles', 'categoryPermissions'])
            ->with('activeSubscriptions.plan')
            ->findOrFail($id);

        $this->authorize('view', $user);

        return Inertia::render('Users/FrontendShow', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar ? asset(Storage::url($user->avatar)) : null,
                'roles' => $user->roles->pluck('name'),
                'registration_source' => $user->registration_source,
                'categories' => $user->categoryPermissions()
                    ->withPivot('permission')
                    ->get()
                    ->map(fn ($cat) => [
                        'id' => $cat->id,
                        'title' => $cat->title,
                        'permission' => $cat->pivot->permission,
                    ]),
                'subscriptions' => $user->activeSubscriptions->map(fn ($sub) => [
                    'id' => $sub->id,
                    'status' => $sub->status,
                    'starts_at' => $sub->starts_at?->format('Y-m-d'),
                    'ends_at' => $sub->ends_at?->format('Y-m-d'),
                    'plan' => $sub->plan ? [
                        'id' => $sub->plan->id,
                        'name' => $sub->plan->name,
                        'slug' => $sub->plan->slug,
                        'price' => $sub->plan->price,
                        'currency' => $sub->plan->currency,
                        'max_categories' => $sub->plan->max_categories,
                        'max_documents' => $sub->plan->max_documents,
                        'max_text_contents' => $sub->plan->max_text_contents,
                        'max_storage_mb' => $sub->plan->max_storage_mb,
                    ] : null,
                ]),
                'created_at' => $user->created_at?->diffForHumans(),
            ],
        ]);
    }

    public function edit(Request $request, string $id)
    {
        $user = User::where('registration_source', 'frontend')->findOrFail($id);
        $this->authorize('update', $user);

        return Inertia::render('Users/FrontendUpdate', [
            'user' => $user,
            'userRoles' => $user->roles()->pluck('name')->all(),
            'roles' => \Spatie\Permission\Models\Role::pluck('name')->all(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::where('registration_source', 'frontend')->findOrFail($id);
        $this->authorize('update', $user);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$id],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->password) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->save();

        if ($request->has('roles')) {
            $roles = (array) $request->roles;
            if (in_array('Admin', $roles) || in_array('Super Admin', $roles)) {
                $roles = \Spatie\Permission\Models\Role::pluck('name')->all();
            }
            $user->syncRoles($roles);
        }

        return to_route('frontend-users.index')
            ->with('success', 'Frontend user updated successfully!');
    }

    public function destroy(string $id)
    {
        $user = User::where('registration_source', 'frontend')->findOrFail($id);
        $this->authorize('delete', $user);
        $user->delete();

        return to_route('frontend-users.index')
            ->with('success', 'Frontend user deleted successfully!');
    }

    public function categories(Request $request, string $id)
    {
        $user = User::where('registration_source', 'frontend')->findOrFail($id);
        $this->authorize('view', $user);
        $perPage = min(max((int) $request->integer('per_page', 10), 5), 100);

        $assignedCategories = $user->categoryPermissions()
            ->withPivot('permission')
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($cat) {
                return [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'description' => $cat->description,
                    'permission' => $cat->pivot->permission,
                ];
            });

        $allCategories = Category::orderBy('title')->get(['id', 'title', 'description']);

        return Inertia::render('Users/FrontendCategories', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'assignedCategories' => $assignedCategories->items(),
            'allCategories' => $allCategories,
            'availablePermissions' => ['view', 'create', 'edit', 'delete', 'manage'],
            'pagination' => [
                'current_page' => $assignedCategories->currentPage(),
                'last_page' => $assignedCategories->lastPage(),
                'per_page' => $assignedCategories->perPage(),
                'total' => $assignedCategories->total(),
            ],
            'filters' => ['per_page' => $perPage],
        ]);
    }

    public function storeCategories(Request $request, string $id)
    {
        $user = User::where('registration_source', 'frontend')->findOrFail($id);
        $this->authorize('assignCategories', $user);

        $validated = $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.category_id' => ['required', 'integer', 'exists:categories,id'],
            'assignments.*.permissions' => ['required', 'array'],
            'assignments.*.permissions.*' => ['required', 'string', 'in:view,create,edit,delete,manage'],
        ]);

        foreach ($validated['assignments'] as $assignment) {
            $category = Category::findOrFail($assignment['category_id']);
            $permission = implode(',', $assignment['permissions']);
            $category->users()->syncWithoutDetaching([$user->id => ['permission' => $permission]]);
        }

        return to_route('frontend-users.categories', $user->id)
            ->with('success', 'Categories assigned successfully!');
    }

    public function removeCategory(Request $request, string $userId, string $categoryId)
    {
        $user = User::where('registration_source', 'frontend')->findOrFail($userId);
        $this->authorize('assignCategories', $user);
        $category = Category::findOrFail($categoryId);
        $category->users()->detach($user->id);

        return to_route('frontend-users.categories', $user->id)
            ->with('success', 'Category removed successfully!');
    }

    public function assignPlan(Request $request, string $id)
    {
        $user = User::where('registration_source', 'frontend')->findOrFail($id);
        $this->authorize('assignPlan', $user);

        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'subscription_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:active,cancelled,expired,pending'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $plan = \App\Models\SubscriptionPlan::findOrFail($validated['subscription_plan_id']);

        if ($request->has('subscription_id')) {
            $subscription = UserSubscription::where('user_id', $user->id)
                ->where('id', $validated['subscription_id'] ?? $request->subscription_id)
                ->firstOrFail();

            $subscription->update([
                'subscription_plan_id' => $plan->id,
                'status' => $validated['status'] ?? $subscription->status,
                'starts_at' => $validated['starts_at'] ?? $subscription->starts_at,
                'ends_at' => $validated['ends_at'] ?? ($plan->duration_days ? now()->addDays($plan->duration_days) : null),
            ]);

            // Auto-assign plan's default categories to the user
            $this->syncPlanCategoriesToUser($user, $plan);

            return to_route('frontend-users.show', $user->id)
                ->with('success', 'Subscription updated successfully!');
        }

        DB::transaction(function () use ($user, $plan, $validated) {
            $status = $validated['status'] ?? 'active';

            UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $validated['starts_at'] ?? now(),
                'ends_at' => $validated['ends_at'] ?? ($plan->duration_days ? now()->addDays($plan->duration_days) : null),
            ]);

            // Auto-assign plan's default categories to the user
            $this->syncPlanCategoriesToUser($user, $plan);
        });

        return to_route('frontend-users.show', $user->id)
            ->with('success', 'Plan assigned successfully!');
    }    public function cancelPlan(Request $request, string $id)
    {
        $user = User::where('registration_source', 'frontend')->findOrFail($id);
        $this->authorize('assignPlan', $user);

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->firstOrFail();

        $subscription->update([
            'status' => 'cancelled', 'cancelled_at' => now(),
        ]);

        return to_route('frontend-users.show', $user->id)
            ->with('success', 'Subscription cancelled successfully!');
    }

    /**
     * Sync a plan's default categories to a user with 'view' permission.
     */
    private function syncPlanCategoriesToUser(User $user, \App\Models\SubscriptionPlan $plan): void
    {
        $planCategories = $plan->categories;

        foreach ($planCategories as $category) {
            // Only add if the user doesn't already have a permission on this category
            $existing = $category->users()
                ->where('user_id', $user->id)
                ->first();

            if (! $existing) {
                $category->users()->attach($user->id, ['permission' => 'view']);
            }
        }
    }
}
