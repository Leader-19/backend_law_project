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
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'registration_source',
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
        ];
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

    public function canCreateCategory(): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        $plan = $this->currentPlan()->first();

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

        $plan = $this->currentPlan()->first();

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

        $plan = $this->currentPlan()->first();

        return $plan?->max_categories;
    }

    public function documentLimit(): ?int
    {
        if ($this->hasRole('Admin')) {
            return null;
        }

        $plan = $this->currentPlan()->first();

        return $plan?->max_documents;
    }

    public function canCreateTextContent(): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        $plan = $this->currentPlan()->first();

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

        $plan = $this->currentPlan()->first();

        return $plan?->max_text_contents;
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

        $categoryIds = $this->currentPlan()->first()?->categories()->pluck('categories.id')->all() ?? [];

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
            $allAncestorIds = [];
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
                    if (! in_array($cat->id, $allAncestorIds) && ! in_array($cat->id, $categoryIds)) {
                        $allAncestorIds[] = $cat->id;
                    }
                }

                $pending = $found->pluck('parent_id')
                    ->filter()
                    ->reject(fn ($id) => in_array($id, $allAncestorIds) || in_array($id, $categoryIds))
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
        $plan = $this->currentPlan()->with('categories')->first();
        if (! $plan) {
            return false;
        }

        $planCategoryIds = $plan->categories->pluck('id')->all();

        if (in_array($category->id, $planCategoryIds)) {
            return true;
        }

        // Check ancestors using batch iterative approach
        $ancestorIds = [];
        $pending = array_filter([$category->parent_id]);

        while (! empty($pending)) {
            $found = Category::whereIn('id', $pending)
                ->select('id', 'parent_id')
                ->get();

            foreach ($found as $cat) {
                $ancestorIds[] = $cat->id;
                if (in_array($cat->id, $planCategoryIds)) {
                    return true;
                }
            }

            $pending = $found->pluck('parent_id')
                ->filter()
                ->reject(fn ($id) => in_array($id, $ancestorIds))
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
