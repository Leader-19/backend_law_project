<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $allCategories = Category::with('documents')->withCount('documents')->orderBy('title')->get();
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
                'documents' => $category->documents->map(function ($doc) {
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
                'documents' => $category->documents->map(function ($doc) {
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
            ],
        ]);

        $category = Category::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::notIn($this->categoryAndDescendantIds($category->id)),
                Rule::exists('categories', 'id'),
            ],
        ]);

        $category->update($validated);

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

    /**
     * Return a category and every nested child, to prevent circular trees.
     *
     * @return array<int>
     */
    private function categoryAndDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $pending = [$categoryId];

        while ($pending !== []) {
            $pending = Category::whereIn('parent_id', $pending)->pluck('id')->all();
            $ids = [...$ids, ...$pending];
        }

        return $ids;
    }
}
