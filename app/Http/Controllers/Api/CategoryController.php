<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('Admin');
        $viewableIds = $isAdmin ? null : ($user?->getViewableCategoryIds() ?? []);

        if (! $isAdmin && empty($viewableIds)) {
            return response()->json([
                'status' => 'success',
                'categories' => [],
            ]);
        }

        $cacheKey = $isAdmin
            ? 'api.categories.tree.admin'
            : 'api.categories.tree.' . md5(implode(',', $viewableIds));

        $categories = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($isAdmin, $viewableIds) {
            // Only load categories + document count (NO full documents)
            $all = Category::query()
                ->withCount('documents')
                ->when(! $isAdmin, fn ($q) => $q->whereIn('id', $viewableIds))
                ->orderBy('title')
                ->get(['id', 'title', 'description', 'parent_id']);

            return $this->buildTree($all);
        });

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
        ]);
    }

    public function myCategories(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $viewableIds = $user->getViewableCategoryIds();

        if (empty($viewableIds)) {
            return response()->json([
                'status' => 'success',
                'categories' => [],
            ]);
        }

        $categories = Category::whereIn('id', $viewableIds)
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'parent_id']);

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
        ]);
    }

    public function show(string $id)
    {
        $category = Category::with(['parent:id,title'])
            ->withCount('documents')
            ->findOrFail($id);

        // Only load direct children (not the whole tree)
        $subcategories = Category::where('parent_id', $category->id)
            ->withCount('documents')
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'parent_id']);

        // Latest documents only (limit 20)
        $documents = $category->documents()
            ->select('id', 'doc_name', 'doc_title', 'description', 'doc_upload', 'image', 'category_id', 'created_at')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'status' => 'success',
            'category' => [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'title' => $category->parent->title,
                ] : null,
                'documents_count' => $category->documents_count,
                'documents' => $documents,
                'subcategories' => $subcategories->map(fn ($sub) => [
                    'id' => $sub->id,
                    'title' => $sub->title,
                    'description' => $sub->description,
                    'parent_id' => $sub->parent_id,
                    'documents_count' => $sub->documents_count,
                ]),
            ],
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $this->clearCategoryCaches();

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    public function update(CategoryRequest $request, string $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        $this->clearCategoryCaches();

        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully.',
            'category' => $category->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        $this->clearCategoryCaches();

        return response()->noContent();
    }

    private function buildTree($categories, $parentId = null)
    {
        return $categories
            ->where('parent_id', $parentId)
            ->values()
            ->map(function ($category) use ($categories) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                    'documents_count' => $category->documents_count,
                    'documents' => [], // loaded on demand in show()
                    'subcategories' => $this->buildTree($categories, $category->id),
                ];
            })
            ->all();
    }

    private function clearCategoryCaches(): void
    {
        Cache::forget('api.categories.tree.admin');
        // For user-specific keys you can use tags if Redis is available
        // or just rely on short TTL (10 min)
    }
}
