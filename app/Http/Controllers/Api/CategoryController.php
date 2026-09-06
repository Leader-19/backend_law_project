<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && ! $user->hasRole('Admin')) {
            $viewableIds = $user->getViewableCategoryIds();

            if (empty($viewableIds)) {
                return response()->json([
                    'status' => 'success',
                    'categories' => [],
                ]);
            }

            $allCategories = Category::with('documents')->withCount('documents')
                ->whereIn('id', $viewableIds)
                ->orderBy('title')
                ->get();
        } else {
            $allCategories = Category::with('documents')->withCount('documents')->orderBy('title')->get();
        }

        $categoriesById = $allCategories->keyBy('id');

        $childrenMap = [];
        foreach ($allCategories as $category) {
            if ($category->parent_id) {
                $childrenMap[$category->parent_id][] = $category;
            }
        }

        $mapCategory = function ($category) use ($childrenMap, &$mapCategory) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'documents_count' => $category->documents_count,
                'documents' => collect($category->documents)->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'doc_name' => $doc->doc_name,
                        'doc_title' => $doc->doc_title,
                        'description' => $doc->description,
                        'doc_upload' => $doc->doc_upload,
                        'image' => $doc->image,
                    ];
                }),
                'subcategories' => isset($childrenMap[$category->id])
                    ? collect($childrenMap[$category->id])->map($mapCategory)->all()
                    : [],
            ];
        };

        $rootCategories = $allCategories->whereNull('parent_id');

        return response()->json([
            'status' => 'success',
            'categories' => $rootCategories->map($mapCategory)->all(),
        ]);
    }

    public function myCategories(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $viewableIds = $user->getViewableCategoryIds();

        $categories = Category::whereIn('id', $viewableIds)
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'parent_id'])
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                ];
            });

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
        ]);
    }

    public function show(string $id)
    {
        $category = Category::with(['parent:id,title', 'documents'])->withCount('documents')->findOrFail($id);

        $allCategories = Category::with('documents')->withCount('documents')->orderBy('title')->get();

        $childrenMap = [];
        foreach ($allCategories as $cat) {
            if ($cat->parent_id) {
                $childrenMap[$cat->parent_id][] = $cat;
            }
        }

        $mapCategory = function ($category) use ($childrenMap, &$mapCategory) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'title' => $category->parent->title,
                ] : null,
                'documents_count' => $category->documents_count,
                'documents' => collect($category->documents)->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'doc_name' => $doc->doc_name,
                        'doc_title' => $doc->doc_title,
                        'description' => $doc->description,
                        'doc_upload' => $doc->doc_upload,
                        'image' => $doc->image,
                    ];
                }),
                'subcategories' => isset($childrenMap[$category->id])
                    ? collect($childrenMap[$category->id])->map($mapCategory)->all()
                    : [],
            ];
        };

        return response()->json([
            'status' => 'success',
            'category' => $mapCategory($category),
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

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

        return response()->noContent();
    }
}
