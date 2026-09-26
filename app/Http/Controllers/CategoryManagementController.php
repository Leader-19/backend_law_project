<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CategoryManagementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->get('per_page', 10), 5), 50);
        $page = max((int) $request->get('page', 1), 1);
        $search = trim((string) $request->get('search', ''));
        $categoryId = $request->get('category_id');

        $user = $request->user();
        $isAdmin = $user->hasRole('Admin');
        $viewableIds = $isAdmin ? null : $user->getViewableCategoryIds();

        // Safer way to load the tree (avoids broken recursive relation)
        $cacheKey = $isAdmin
            ? 'categories.tree.admin'
            : 'categories.tree.'.$user->id;

        $categories = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($isAdmin, $viewableIds) {
            $all = Category::query()
                ->when(! $isAdmin, fn ($q) => $q->whereIn('id', $viewableIds))
                ->orderBy('title')
                ->get(['id', 'title', 'description', 'parent_id']);

            return $this->buildTree($all);
        });

        $documents = Document::query()
            ->with(['category:id,title', 'user:id,name'])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('doc_name', 'like', "%{$search}%")
                        ->orWhere('doc_title', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return Inertia::render('Category/CategoryManagement', [
            'categories' => $categories,
            'documents' => $documents->items(),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Build nested tree from flat collection (no recursive relation needed).
     */
    private function buildTree($categories, $parentId = null)
    {
        return $categories
            ->where('parent_id', $parentId)
            ->values()
            ->map(function ($category) use ($categories) {
                $category->children = $this->buildTree($categories, $category->id);

                return $category;
            });
    }
}
