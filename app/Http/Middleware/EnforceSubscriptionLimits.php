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
            $subscription = $user->activeSubscription()->with('plan')->first();

            if (! $subscription?->plan) {
                return response()->json([
                    'message' => 'An active subscription is required for this action.',
                ], 403);
            }

            $plan = $subscription->plan;

            if ($type === 'category' && $plan->max_categories) {
                $currentCount = $user->categories()->count();
                if ($currentCount >= $plan->max_categories) {
                    return response()->json([
                        'message' => "You have reached your plan's category limit of {$plan->max_categories}.",
                        'limit' => $plan->max_categories,
                        'current' => $currentCount,
                    ], 403);
                }
            }

            if ($type === 'document' && $plan->max_documents) {
                $currentCount = $user->documents()->count();
                if ($currentCount >= $plan->max_documents) {
                    return response()->json([
                        'message' => "You have reached your plan's document limit of {$plan->max_documents}.",
                        'limit' => $plan->max_documents,
                        'current' => $currentCount,
                    ], 403);
                }
            }

            if ($type === 'text_content' && $plan->max_text_contents) {
                $currentCount = $user->textContents()->count();
                if ($currentCount >= $plan->max_text_contents) {
                    return response()->json([
                        'message' => "You have reached your plan's text content limit of {$plan->max_text_contents}.",
                        'limit' => $plan->max_text_contents,
                        'current' => $currentCount,
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
