<?php

namespace App\Http\Middleware;

use App\Services\DocumentLimitService;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscriptionLimits
{
    public function handle(Request $request, Closure $next, string $type = 'category'): Response
    {
        $user = Auth::user();

        if ($user && ! $user->hasRole('Admin')) {
            // Guarantees a Free subscription for legacy as well as newly registered users.
            app(SubscriptionService::class)->ensureFreeSubscription($user);

            // Aggregate limits across all active plans (0 means unlimited)
            $limitColumn = match ($type) {
                'category' => 'max_categories',
                'document' => 'max_documents',
                'text_content' => 'max_text_contents',
                default => null,
            };

            if ($type === 'document') {
                try {
                    app(DocumentLimitService::class)->ensureCanCreate($user);
                } catch (AuthorizationException $exception) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => $exception->getMessage(), 'usage' => app(DocumentLimitService::class)->usage($user)], 403);
                    }

                    return redirect()->route('billing.pricing')->with('error', $exception->getMessage());
                }
            } elseif ($limitColumn) {
                // Existing category/text-content limits remain server-enforced.
                $limit = $user->{$type === 'category' ? 'categoryLimit' : 'textContentLimit'}();
                $count = $type === 'category' ? $user->categories()->count() : $user->textContents()->count();
                if ($limit !== null && $count >= $limit) {
                    return response()->json(['message' => "You have reached your plan's {$type} limit."], 403);
                }
            }
        }

        return $next($request);
    }
}
