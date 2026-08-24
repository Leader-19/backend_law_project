<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 5), 100);
        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));
        $registrationSource = $request->query('registration_source');

        abort_unless(in_array($registrationSource, [null, 'frontend', 'admin'], true), 422);

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($role !== '', fn ($query) => $query->whereHas('roles', fn ($q) => $q
                ->where('name', $role)))
            ->when($registrationSource, fn ($query, $source) => $query->where('registration_source', $source))
            ->with('roles')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($user) {
                $subscription = $user->activeSubscription()->with('plan')->first();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar ? asset(Storage::url($user->avatar)) : null,
                    'registration_source' => $user->registration_source,
                    'roles' => $user->roles->pluck('name'),
                    'subscription' => $subscription ? [
                        'id' => $subscription->id,
                        'status' => $subscription->status,
                        'starts_at' => $subscription->starts_at?->format('Y-m-d'),
                        'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                        'plan' => $subscription->plan ? [
                            'id' => $subscription->plan->id,
                            'name' => $subscription->plan->name,
                            'slug' => $subscription->plan->slug,
                            'price' => $subscription->plan->price,
                            'currency' => $subscription->plan->currency,
                            'max_categories' => $subscription->plan->max_categories,
                            'max_documents' => $subscription->plan->max_documents,
                            'max_text_contents' => $subscription->plan->max_text_contents,
                            'max_storage_mb' => $subscription->plan->max_storage_mb,
                        ] : null,
                    ] : null,
                    'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'filters' => ['search' => $search, 'role' => $role, 'registration_source' => $registrationSource, 'per_page' => $perPage],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'registration_source' => ['nullable', 'string', 'in:frontend,admin'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'registration_source' => $validated['registration_source'] ?? 'admin',
        ]);

        if (!empty($validated['roles'])) {
            $roles = $validated['roles'];
            if (in_array('Admin', $roles) || in_array('admin', $roles) || in_array('Super Admin', $roles)) {
                $roles = \Spatie\Permission\Models\Role::pluck('name')->all();
            }
            $user->syncRoles($roles);
        } else {
            $user->assignRole('Normal');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully.',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function show(string $id)
    {
        $user = User::with('roles', 'categoryPermissions', 'activeSubscription.plan')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar ? asset(Storage::url($user->avatar)) : null,
                'registration_source' => $user->registration_source,
                'roles' => $user->roles->pluck('name'),
                'categories' => $user->categoryPermissions()
                    ->withPivot('permission')
                    ->get()
                    ->map(fn ($cat) => [
                        'id' => $cat->id,
                        'title' => $cat->title,
                        'permission' => $cat->pivot->permission,
                    ]),
                'subscription' => $user->activeSubscription ? [
                    'id' => $user->activeSubscription->id,
                    'status' => $user->activeSubscription->status,
                    'starts_at' => $user->activeSubscription->starts_at?->format('Y-m-d'),
                    'ends_at' => $user->activeSubscription->ends_at?->format('Y-m-d'),
                    'plan' => $user->activeSubscription->plan ? [
                        'id' => $user->activeSubscription->plan->id,
                        'name' => $user->activeSubscription->plan->name,
                        'slug' => $user->activeSubscription->plan->slug,
                        'price' => $user->activeSubscription->plan->price,
                        'currency' => $user->activeSubscription->plan->currency,
                        'max_categories' => $user->activeSubscription->plan->max_categories,
                        'max_documents' => $user->activeSubscription->plan->max_documents,
                        'max_text_contents' => $user->activeSubscription->plan->max_text_contents,
                        'max_storage_mb' => $user->activeSubscription->plan->max_storage_mb,
                    ] : null,
                ] : null,
                'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (isset($validated['roles'])) {
            $roles = $validated['roles'];
            if (in_array('Admin', $roles) || in_array('admin', $roles) || in_array('Super Admin', $roles)) {
                $roles = \Spatie\Permission\Models\Role::pluck('name')->all();
            }
            $user->syncRoles($roles);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully.',
            'user' => $this->userPayload($user->load('roles')),
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }

    public function assignCategories(Request $request, string $id)
    {
        $user = User::findOrFail($id);

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

        return response()->json([
            'status' => 'success',
            'message' => 'Categories assigned successfully.',
        ]);
    }

    public function removeCategory(Request $request, string $userId, string $categoryId)
    {
        $user = User::findOrFail($userId);
        $category = Category::findOrFail($categoryId);
        $category->users()->detach($user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Category removed successfully.',
        ]);
    }

    public function assignPlan(Request $request, string $id)
    {
        $user = User::findOrFail($id);

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
            foreach ($plan->categories as $category) {
                $existing = $category->users()
                    ->where('user_id', $user->id)
                    ->first();

                if (! $existing) {
                    $category->users()->attach($user->id, ['permission' => 'view']);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription updated successfully.',
                'subscription' => $subscription->fresh()->load('plan'),
            ]);
        }

        $subscription = DB::transaction(function () use ($user, $plan, $validated) {
            $status = $validated['status'] ?? 'active';

            if ($status === 'active') {
                UserSubscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            }

            $sub = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $validated['starts_at'] ?? now(),
                'ends_at' => $validated['ends_at'] ?? ($plan->duration_days ? now()->addDays($plan->duration_days) : null),
            ]);

            // Auto-assign plan's default categories to the user
            if ($status === 'active') {
                foreach ($plan->categories as $category) {
                    $existing = $category->users()
                        ->where('user_id', $user->id)
                        ->first();

                    if (! $existing) {
                        $category->users()->attach($user->id, ['permission' => 'view']);
                    }
                }
            }

            return $sub;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Plan assigned successfully.',
            'subscription' => $subscription->load('plan'),
        ], 201);
    }

    public function cancelPlan(Request $request, string $id)
    {
        $user = User::findOrFail($id);

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

    public function availablePlans()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'description', 'price', 'currency', 'duration_days', 'features', 'max_categories', 'max_documents', 'max_text_contents', 'max_storage_mb'])
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

    public function categories()
    {
        $categories = Category::orderBy('title')->get(['id', 'title', 'description', 'parent_id', 'user_id']);

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar ? asset(Storage::url($user->avatar)) : null,
            'roles' => $user->getRoleNames()->values(),
            'registration_source' => $user->registration_source,
        ];
    }
}
