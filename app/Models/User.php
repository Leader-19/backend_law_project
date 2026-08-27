<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */    protected $fillable = [
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
        return $this->isApproved();
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
     * Get the current plan, using cache to avoid repeated queries.
     */
    public function getCurrentPlanCached(): ?SubscriptionPlan
    {
        $activeSubscription = $this->activeSubscription()->first();

        if (! $activeSubscription) {
            return null;
        }

        return SubscriptionPlan::getCachedWithCategories($activeSubscription->subscription_plan_id);
    }

    public function canCreateCategory(): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        $plan = $this->getCurrentPlanCached();

        if (! $plan) {
            return false;
        }

        if (! $plan->max_categories) {
            return true;
        }

        return $this->categories()->count() < $plan->max_categories;
    }

    public function canCreateDocument(): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        $plan = $this->getCurrentPlanCached();

        if (! $plan) {
            return false;
        }

        if (! $plan->max_documents) {
            return true;
        }

        return $this->documents()->count() < $plan->max_documents;
    }

    public function categoryLimit(): ?int
    {
        if ($this->hasRole('Admin')) {
            return null;
        }

        return $this->getCurrentPlanCached()?->max_categories;
    }

    public function documentLimit(): ?int
    {
        if ($this->hasRole('Admin')) {
            return null;
        }

        return $this->getCurrentPlanCached()?->max_documents;
    }

    public function canCreateTextContent(): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        $plan = $this->getCurrentPlanCached();

        if (! $plan) {
            return false;
        }

        if (! $plan->max_text_contents) {
            return true;
        }

        return $this->textContents()->count() < $plan->max_text_contents;
    }

    public function textContentLimit(): ?int
    {
        if ($this->hasRole('Admin')) {
            return null;
        }

        return $this->getCurrentPlanCached()?->max_text_contents;
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
        return $this->belongsToMany(Category::class, 'category_user')->withPivot('permission');
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

        // Get all ancestor IDs (including the category itself) in one query
        $candidateIds = $this->getCategoryAndAncestorIds($category);

        // Check direct + inherited permissions in a single query
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
                ->reject(fn ($id) => in_array($id, $ids))
                ->values()
                ->all();
        }

        return $ids;
    }

    // Get all category IDs this user can view (including inherited)
    public function getViewableCategoryIds(): array
    {
        if ($this->hasRole('Admin')) {
            return Category::pluck('id')->all();
        }

        $activeSubscription = $this->activeSubscription()->first();
        $categoryIds = $activeSubscription
            ? SubscriptionPlan::getPlanCategoryIds($activeSubscription->subscription_plan_id)
            : [];

        $categoryIds = [...$categoryIds, ...$this->categoryPermissions()
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
            ->all()];

        $roleIds = $this->roles()->pluck('id');
        if ($roleIds->isNotEmpty()) {
            $teamCategoryIds = Category::query()
                ->whereHas('teams', fn ($query) => $query
                    ->whereIn('roles.id', $roleIds)
                    ->whereIn('category_role.permission', ['view', 'manage']))
                ->pluck('id')
                ->all();
            $categoryIds = [...$categoryIds, ...$teamCategoryIds];
        }

        // Find all ancestors of viewable categories using batch iterative approach
        if ($categoryIds !== []) {
            $categoryIdsSet = array_flip($categoryIds);
            $allAncestorIds = [];
            $allAncestorIdsSet = [];
            $pending = Category::whereIn('id', $categoryIds)
                ->pluck('parent_id')
                ->filter()
                ->values()
                ->all();

            while (! empty($pending)) {
                $found = Category::whereIn('id', $pending)
                    ->select('id', 'parent_id')
                    ->get();

                foreach ($found as $cat) {
                    if (! isset($allAncestorIdsSet[$cat->id]) && ! isset($categoryIdsSet[$cat->id])) {
                        $allAncestorIds[] = $cat->id;
                        $allAncestorIdsSet[$cat->id] = true;
                    }
                }

                $pending = $found->pluck('parent_id')
                    ->filter()
                    ->reject(fn ($id) => isset($allAncestorIdsSet[$id]) || isset($categoryIdsSet[$id]))
                    ->values()
                    ->all();
            }

            $categoryIds = [...$categoryIds, ...$allAncestorIds];
        }

        return array_unique($categoryIds);
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
        $activeSubscription = $this->activeSubscription()->first();
        if (! $activeSubscription) {
            return false;
        }

        $planCategoryIds = SubscriptionPlan::getPlanCategoryIds($activeSubscription->subscription_plan_id);
        $planCategoryIdsSet = array_flip($planCategoryIds);

        if (isset($planCategoryIdsSet[$category->id])) {
            return true;
        }

        // Check ancestors using batch iterative approach
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

    protected static function booted(): void
    {
        // Deleting a user cascade-deletes their documents and categories at the
        // DB level, bypassing Document model events - remove the files first.
        static::deleting(function (User $user) {
            $cleanup = function ($document) {
                if ($document->doc_upload) {
                    Storage::disk('public')->delete($document->doc_upload);
                }
                if ($document->image) {
                    Storage::disk('public')->delete($document->image);
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
        });
    }
}
