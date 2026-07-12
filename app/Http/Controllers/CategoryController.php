<?php

namespace App\Http\Controllers;

use App\Services\Categories\CategoriesService;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    protected $service;

    public function __construct(CategoriesService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $parentIds = $request->input('parent_ids', []);
        $parentIds = is_array($parentIds) ? $parentIds : [];
        $parentIds = validator(['parent_ids' => $parentIds], [
            'parent_ids' => ['array'],
            'parent_ids.*' => ['integer', 'exists:categories,id'],
        ])->validate()['parent_ids'];

        $paginated = $this->service->getPaginated($perPage, $parentIds)->appends($request->except('page'));

        $categories = collect($paginated->items())->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent_title' => $category->parent?->title,
                'documents_count' => $category->documents_count ?? 0,
            ];
        })->toArray();

        return Inertia::render("Category/CategoryIndex", [
            'categories' => $categories,
            // The list can be paginated, so provide every category separately
            // for the nested-category selector.
            'parents' => Category::query()
                ->orderBy('title')
                ->get(['id', 'title', 'parent_id']),
            'selectedParentIds' => $parentIds,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render("Category/CategoryCreate", [
            'parents' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ]);

        $this->service->store($request);

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function show(string $id)
    {
        $category = $this->service->find($id);
        $documents = \App\Models\Document::where('category_id', $id)->paginate(10);
        $subcategories = \App\Models\Category::where('parent_id', $id)
            ->withCount('documents')
            ->get();
        $allCategories = Category::whereNull('parent_id')->with('childrenRecursive')->get();

        return Inertia::render("Category/CategoryDetails", [
            "category" => $category,
            'categories' => $allCategories,
            'documents' => $documents->items(),
            'subcategories' => $subcategories,
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ]
        ]);
    }

    public function edit(string $id)
    {
        return Inertia::render("Category/CategoryUpdate", [
            "category" => $this->service->find($id),
            'parents' => Category::whereNotIn('id', $this->categoryAndDescendantIds((int) $id))
                ->orderBy('title')
                ->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                Rule::notIn($this->categoryAndDescendantIds((int) $id)),
            ],
        ]);

        $this->service->update($request, $id);

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);

        foreach ($validated['ids'] as $id) {
            $this->service->delete($id);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Selected categories deleted successfully!');
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
