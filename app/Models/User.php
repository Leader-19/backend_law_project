<?php

namespace App\Models;

use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Notifications\VerifyApiEmail;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, MustVerifyEmail, Notifiable, TwoFactorAuthenticatable;

    /**
     * Send the email verification notification. Overridden so the link
     * points at the API's own signed route (api.verification.verify)
     * instead of Fortify's session-guarded one, since the public SPA
     * authenticates with a Bearer token, not a browser session.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyApiEmail);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'status',
        'password',
        'avatar',
        'registration_source',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // Account status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_INACTIVE,
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function canLogin(): bool
    {
        // Public registration does not require an administrator approval step.
        // Keep explicit rejected/inactive accounts blocked, but allow legacy
        // pending users to sign in as well.
        return ! $this->isRejected() && ! $this->isInactive();
    }

    // User can upload many Documents
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // User can create many TextContents
    public function textContents()
    {
        return $this->hasMany(TextContent::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at')
            ->latest('id');
    }

    /** Get all active subscriptions for this user. */
    public function activeSubscriptions()
    {
        return $this->hasMany(UserSubscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at')
            ->latest('id');
    }

    public function currentPlan()
    {
        return $this->hasOneThrough(
            SubscriptionPlan::class,
            UserSubscription::class,
            'user_id',
            'id',
            'id',
            'subscription_plan_id'
        )->where('user_subscriptions.status', 'active')
            ->where(function ($query) {
                $query->whereNull('user_subscriptions.ends_at')->orWhere('user_subscriptions.ends_at', '>', now());
            })
            ->latest('user_subscriptions.starts_at')
            ->latest('user_subscriptions.id');
    }

    /**
     * Get all active plans, using cache to avoid repeated queries.
     * Returns a Collection of SubscriptionPlan models.
     */
    public function getActivePlans(): Collection
    {
        $subscriptions = $this->activeSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return collect();
        }

        return $subscriptions->map(fn ($sub) => SubscriptionPlan::getCachedWithCategories($sub->subscription_plan_id))
            ->filter();
    }

    /**
     * Get the current plan, using cache to avoid repeated queries.
     * Returns the latest active plan for backward compatibility.
     */
    public function getCurrentPlanCached(): ?SubscriptionPlan
    {
        $activeSubscription = $this->activeSubscription()->first();

        if (! $activeSubscription) {
            return null;
        }

        return SubscriptionPlan::getCachedWithCategories($activeSubscription->subscription_plan_id);
    }

    /**
     * Get the combined limit across all active plans (0 means unlimited).
     */
    private function getCombinedLimit(string $column): ?int
    {
        $plans = $this->getActivePlans();

        if ($plans->isEmpty()) {
            return null;
        }

        $total = 0;
        $hasUnlimited = false;

        foreach ($plans as $plan) {
            $value = $plan->{$column};
            if (! $value) {
                $hasUnlimited = true;
            } else {
                $total += $value;
            }
        }

        return $hasUnlimited ? null : $total;
    }

    public function canCreateCategory(): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        $limit = $this->getCombinedLimit('max_categories');

        if ($limit === null) {
            // No plan or unlimited
            return $this->getActivePlans()->isNotEmpty();
        }

        return $this->categories()->count() < $limit;
    }

    public function canCreateDocument(): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        $limit = $this->getCombinedLimit('max_documents');

        if ($limit === null) {
            return $this->getActivePlans()->isNotEmpty();
        }

        return $this->documents()->count() < $limit;
    }

    public function categoryLimit(): ?int
    {
        if ($this->hasRole('Admin')) {
            return null;
        }

        return $this->getCombinedLimit('max_categories');
    }

    public function documentLimit(): ?int
    {
        if ($this->hasRole('Admin')) {
            return null;
        }

        return $this->getCombinedLimit('max_documents');
    }

    public function canCreateTextContent(): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        $limit = $this->getCombinedLimit('max_text_contents');

        if ($limit === null) {
            return $this->getActivePlans()->isNotEmpty();
        }

        return $this->textContents()->count() < $limit;
    }

    public function textContentLimit(): ?int
    {
        if ($this->hasRole('Admin')) {
            return null;
        }

        return $this->getCombinedLimit('max_text_contents');
    }

    // User library (saved/favorited documents)
    public function library()
    {
        return $this->belongsToMany(Document::class, 'user_library')->withTimestamps();
    }

    // Reading history
    public function readingHistory()
    {
        return $this->hasMany(ReadingHistory::class);
    }

    // Quiz attempts
    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // Certificates
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    // Contact messages
    public function contactMessages()
    {
        return $this->hasMany(ContactMessage::class);
    }

    // Categories this user has specific permissions for
    public function categoryPermissions()
    {
        // CRITICAL: the pivot column `permission` must be in the index() call.
        // The DB default index for a BelongsToMany pivot is ('user_id', 'category_id'),
        // and this query needs ('user_id', 'category_id', 'permission').
        // Without it, getViewableCategoryIds() and hasCategoryPermission() run the
        // same SELECT ... WHERE user_id = ? and PHP in_array()s across every
        // intermediate category, turning O(n) PHP work into O(n) extra queries.
        return $this->belongsToMany(Category::class, 'category_user')
            ->withPivot('permission');
    }

    // Check if user has a specific permission on a category (including inherited from ancestors)
    public function hasCategoryPermission(string $permission, Category $category): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        if ($permission === 'view' && $this->hasPlanCategoryAccess($category)) {
            return true;
        }

        if ($permission === 'view' && $category->access_type === 'public') {
            return true;
        }

        // Direct + inherited permissions in a single query.
        // The ancestor IDs were already computed in the cacheable getViewableCategoryIds(),
        // and here we only need IDs that intersect the candidate set.
        $candidateIds = $this->getCategoryAndAncestorIds($category);

        $hasPermission = $this->categoryPermissions()
            ->whereIn('category_id', $candidateIds)
            ->where(function ($query) use ($permission) {
                $query->where('permission', $permission)
                    ->orWhere('permission', 'manage')
                    ->orWhere('permission', 'like', "{$permission},%")
                    ->orWhere('permission', 'like', "%,{$permission},%")
                    ->orWhere('permission', 'like', "%,{$permission}");
            })
            ->exists();

        if ($hasPermission) {
            return true;
        }

        return $this->hasTeamCategoryPermission($permission, $candidateIds);
    }

    /**
     * Get a category and all its ancestor IDs (including itself).
     * Uses a batch iterative approach compatible with all databases.
     */
    private function getCategoryAndAncestorIds(Category $category): array
    {
        $ids = [$category->id];
        $pending = array_filter([$category->parent_id]);

        while (! empty($pending)) {
            $found = Category::whereIn('id', $pending)
                ->select('id', 'parent_id')
                ->get();

            foreach ($found as $cat) {
                $ids[] = $cat->id;
            }

            $pending = $found->pluck('parent_id')
                ->filter()
                ->reject(fn ($id) => in_array($id, $ids, true))
                ->values()
                ->all();
        }

        return $ids;
    }

    /**
     * Get the viewable category IDs for the current user.
     *
     * Cached for 30 minutes so that one resolving pass is shared across every
     * page that needs the viewable categories (inertia sidebar, index pages,
     * policy checks, API lookups). The computation is the expensive part:
     * it joins active subscriptions, plan categories, explicit user permissions,
     * team role permissions and the category descendant tree.
     *
     * @return array<int, int>
     */
    public function getViewableCategoryIds(): array
    {
        $cacheKey = 'user.viewable_categories.'.$this->id;

        return Cache::remember($cacheKey, now()->addMinutes(30), function () {
            if ($this->hasRole('Admin')) {
                return Category::pluck('id')->all();
            }

            $publicCategoryIds = Category::where('access_type', 'public')->pluck('id')->all();

            // Single query for both the active subscriptions and the plan categories.
            // Reuse the same arrays across roles, team and descendant logic so the
            // plan/role join is not re-run for every category on the page.
            $planSubscriptionIds = $this->activeSubscriptions()
                ->whereHas('plan', fn ($query) => $query->where('slug', '!=', 'free')->where('price', '>', 0))
                ->get()
                ->pluck('subscription_plan_id')
                ->unique()
                ->values()
                ->all();

            $planCategoryIds = [];
            foreach ($planSubscriptionIds as $planId) {
                $planCategoryIds = array_unique(array_merge($planCategoryIds, SubscriptionPlan::getPlanCategoryIds($planId)));
            }
            $planCategoryIds = array_values(array_unique($planCategoryIds));

            $permissionCategoryIds = $this->categoryPermissions()
                ->where(function ($query) {
                    $query->where('permission', 'view')
                        ->orWhere('permission', 'manage')
                        ->orWhere('permission', 'like', 'view,%')
                        ->orWhere('permission', 'like', '%,view,%')
                        ->orWhere('permission', 'like', '%,view')
                        ->orWhere('permission', 'like', 'manage,%')
                        ->orWhere('permission', 'like', '%,manage,%')
                        ->orWhere('permission', 'like', '%,manage');
                })
                ->pluck('category_id')
                ->all();

            $roleIds = $this->roles()->pluck('id');
            $teamCategoryIds = [];
            if ($roleIds->isNotEmpty()) {
                $teamCategoryIds = Category::query()
                    ->whereHas('teams', fn ($query) => $query
                        ->whereIn('roles.id', $roleIds)
                        ->whereIn('category_role.permission', ['view', 'manage']))
                    ->pluck('id')
                    ->all();
            }

            $categoryIds = array_values(array_unique([
                ...$planCategoryIds,
                ...$permissionCategoryIds,
                ...$publicCategoryIds,
                ...$teamCategoryIds,
            ]));

            $allDescendantIds = [];
            if ($categoryIds !== []) {
                $categoryIdsSet = array_flip($categoryIds);
                $inheritanceSourceIds = array_values(array_unique([...$planCategoryIds, ...$permissionCategoryIds]));
                $pendingDescendants = Category::whereIn('parent_id', $inheritanceSourceIds)
                    ->pluck('id')
                    ->values()
                    ->all();

                while (! empty($pendingDescendants)) {
                    $newDescendants = [];
                    foreach ($pendingDescendants as $dId) {
                        if (! isset($allDescendantIdsSet[$dId]) && ! isset($categoryIdsSet[$dId])) {
                            $allDescendantIds[] = $dId;
                            $allDescendantIdsSet[$dId] = true;
                            $newDescendants[] = $dId;
                        }
                    }

                    if (empty($newDescendants)) {
                        break;
                    }

                    $pendingDescendants = Category::whereIn('parent_id', $newDescendants)
                        ->pluck('id')
                        ->reject(fn ($id) => isset($allDescendantIdsSet[$id]) || isset($categoryIdsSet[$id]))
                        ->values()
                        ->all();
                }
            }

            // Ancestors are deliberately excluded: a visible public child must not
            // make its plan-only parent (or the parent's documents) visible.
            return array_values(array_unique([...$categoryIds, ...$allDescendantIds]));
        });
    }

    private function hasTeamCategoryPermission(string $permission, array $candidateIds): bool
    {
        $roleIds = $this->roles()->pluck('id');
        if ($roleIds->isEmpty()) {
            return false;
        }

        return Category::whereIn('categories.id', $candidateIds)
            ->whereHas('teams', function ($query) use ($roleIds, $permission) {
                $query->whereIn('roles.id', $roleIds)
                    ->whereIn('category_role.permission', [$permission, 'manage']);
            })
            ->exists();
    }

    private function hasPlanCategoryAccess(Category $category): bool
    {
        $planSubscriptionIds = $this->activeSubscriptions()
            ->whereHas('plan', fn ($query) => $query->where('slug', '!=', 'free')->where('price', '>', 0))
            ->get()
            ->pluck('subscription_plan_id')
            ->unique()
            ->values()
            ->all();

        if ($planSubscriptionIds === []) {
            return false;
        }

        $planCategoryIdsSet = [];
        foreach ($planSubscriptionIds as $planId) {
            foreach (SubscriptionPlan::getPlanCategoryIds($planId) as $categoryId) {
                $planCategoryIdsSet[$categoryId] = true;
            }
        }

        $ancestorIdsSet = [];
        $pending = array_filter([$category->parent_id]);

        while (! empty($pending)) {
            $found = Category::whereIn('id', $pending)
                ->select('id', 'parent_id')
                ->get();

            foreach ($found as $cat) {
                $ancestorIdsSet[$cat->id] = true;
                if (isset($planCategoryIdsSet[$cat->id])) {
                    return true;
                }
            }

            $pending = $found->pluck('parent_id')
                ->filter()
                ->reject(fn ($id) => isset($ancestorIdsSet[$id]))
                ->values()
                ->all();
        }

        return false;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? route('avatars.show', basename($this->avatar)) : null;
    }

    protected static function booted(): void
    {
        // Deleting a user cascade-deletes their documents and categories at the
        // DB level, bypassing Document model events - remove the files first.
        static::deleting(function (User $user) {
            $cleanup = function ($document) {
                if ($document->doc_upload) {
                    Storage::disk('s3')->delete($document->doc_upload);
                }
                if ($document->image) {
                    Storage::disk('s3')->delete($document->image);
                }
            };

            foreach ($user->documents as $document) {
                $cleanup($document);
            }

            foreach ($user->categories as $category) {
                foreach ($category->documents as $document) {
                    $cleanup($document);
                }
            }

            if ($user->avatar) {
                Storage::disk('s3')->delete($user->avatar);
            }
        });
    }
}
