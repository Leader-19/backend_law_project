<?php

namespace App\Http\Middleware;

use App\Models\Category;
use Closure;
use Illuminate\Http\Request;

class EnsureUserHasCategoryPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('Admin')) {
            return $next($request);
        }

        $categoryId = $request->route('category');

        if (! $categoryId) {
            abort(403);
        }

        $category = Category::findOrFail($categoryId);

        if ($user->hasCategoryPermission($permission, $category)) {
            return $next($request);
        }

        abort(403);
    }
}
