<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscriptionLimits
{
    public function handle(Request $request, Closure $next, string $type = 'category'): Response
    {
        $user = Auth::user();

        if ($user && ! $user->hasRole('Admin')) {
            $activePlans = $user->activeSubscriptions()->with('plan')->get()
                ->pluck('plan')
                ->filter();

            if ($activePlans->isEmpty()) {
                return response()->json([
                    'message' => 'An active subscription is required for this action.',
                ], 403);
            }

            // Aggregate limits across all active plans (0 means unlimited)
            $limitColumn = match ($type) {
                'category' => 'max_categories',
                'document' => 'max_documents',
                'text_content' => 'max_text_contents',
                default => null,
            };

            if ($limitColumn) {
                $totalLimit = 0;
                $hasUnlimited = false;
                foreach ($activePlans as $plan) {
                    $val = $plan->{$limitColumn};
                    if (! $val) {
                        $hasUnlimited = true;
                    } else {
                        $totalLimit += $val;
                    }
                }

                if (! $hasUnlimited && $totalLimit > 0) {
                    $currentCount = match ($type) {
                        'category' => $user->categories()->count(),
                        'document' => $user->documents()->count(),
                        'text_content' => $user->textContents()->count(),
                        default => 0,
                    };
                    $label = match ($type) {
                        'category' => 'category',
                        'document' => 'document',
                        'text_content' => 'text content',
                        default => $type,
                    };
                    if ($currentCount >= $totalLimit) {
                        return response()->json([
                            'message' => "You have reached your plan's {$label} limit of {$totalLimit}.",
                            'limit' => $totalLimit,
                            'current' => $currentCount,
                        ], 403);
                    }
                }
            }
        }

        return $next($request);
    }
}
