<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class AdministratorBook extends Model
{
    protected $fillable = [
        'administrator_book_category_id',
        'title',
        'name',
        'description',
        'file_path',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AdministratorBookCategory::class, 'administrator_book_category_id');
    }

    /** Subscription plans that are allowed to view and read this book. */
    public function subscriptionPlans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class)
            ->withTimestamps();
    }

    /** Public URL for the uploaded file. */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path
            ? Storage::disk('public')->url($this->file_path)
            : null;
    }

    /**
     * Whether the given user is allowed to view and read this book.
     *
     * Admins always have access. The book must belong to a plan the user
     * has an active subscription on (or have no plan restriction at all).
     */
    public function isAccessibleBy(\App\Models\User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        $planIds = $this->activeSubscriptionsFor($user);

        // No restriction means any logged-in user can read it.
        if (! $this->subscriptionPlans()->exists()) {
            return true;
        }

        return $this->subscriptionPlans()
            ->whereIn('subscription_plans.id', $planIds)
            ->exists();
    }

    /** IDs of all plans for the user's active (non-free) subscriptions. */
    protected function activeSubscriptionsFor(\App\Models\User $user): array
    {
        return $user->activeSubscriptions()
            ->pluck('subscription_plan_id')
            ->all();
    }
}
