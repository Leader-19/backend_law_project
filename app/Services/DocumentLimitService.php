<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DocumentLimitService
{
    public function usage(User $user): array
    {
        $plan = app(SubscriptionService::class)->currentPlanFor($user);
        $used = $user->documents()->count();
        $limit = $user->hasRole('Admin') ? null : $plan->max_documents;

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $limit === null ? null : max(0, $limit - $used),
            'percentage' => $limit === null ? 0 : min(100, (int) round(($used / max(1, $limit)) * 100)),
            'at_limit' => $limit !== null && $used >= $limit,
        ];
    }

    public function ensureCanCreate(User $user, int $quantity = 1): void
    {
        if ($user->hasRole('Admin')) {
            return;
        }
        $usage = $this->usage($user);
        if ($usage['limit'] !== null && $usage['limit'] < $usage['used'] + $quantity) {
            throw new AuthorizationException("You've reached your document limit. Upgrade your plan to add more documents.");
        }
    }
}
