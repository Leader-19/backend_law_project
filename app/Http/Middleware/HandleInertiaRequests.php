<?php

namespace App\Http\Middleware;

use App\Models\Category;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        $categories = [];

        if ($user) {
            if ($user->hasRole('Admin')) {
                $categories = Category::orderBy('title')->get(['id', 'title', 'parent_id', 'description'])->map(fn ($cat) => [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'parent_id' => $cat->parent_id,
                    'description' => $cat->description,
                ])->all();
            } else {
                $viewableIds = $user->getViewableCategoryIds();
                $categories = Category::whereIn('id', $viewableIds)->orderBy('title')->get(['id', 'title', 'parent_id', 'description'])->map(fn ($cat) => [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'parent_id' => $cat->parent_id,
                    'description' => $cat->description,
                ])->all();
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => $user,
                'permissions' => fn () => $user ? $user->getAllPermissions()->pluck('name')->values()->all() : [],
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sidebarCategories' => $categories,
        ];
    }
}
