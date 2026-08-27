<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'duration_days',
        'features',
        'max_categories',
        'max_documents',
        'max_text_contents',
        'max_storage_mb',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /** Categories included with this plan for read access. */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    /**
     * Get all active plans with categories, cached for performance.
     */
    public static function getActivePlansWithCategories(): Collection
    {
        return Cache::remember('subscription_plans_active', now()->addHours(24), function () {
            return static::where('is_active', true)
                ->with('categories')
                ->get();
        });
    }

    /**
     * Get a specific plan with categories, cached for performance.
     */
    public static function getCachedWithCategories(int $id): ?static
    {
        $plans = static::getActivePlansWithCategories();

        return $plans->firstWhere('id', $id);
    }

    /**
     * Get plan category IDs, cached for performance.
     */
    public static function getPlanCategoryIds(int $planId): array
    {
        $plan = static::getCachedWithCategories($planId);

        return $plan ? $plan->categories->pluck('id')->all() : [];
    }

    /**
     * Clear the plans cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('subscription_plans_active');
    }

    /**
     * When the model is saved, clear the cache.
     */
    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }
}
