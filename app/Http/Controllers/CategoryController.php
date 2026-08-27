<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Services\Categories\CategoriesService;
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
        $this->authorize('viewAny', Category::class);

        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $parentIds = $request->input('parent_ids', []);
        $parentIds = is_array($parentIds) ? $parentIds : [];
        $parentIds = validator(['parent_ids' => $parentIds], [
            'parent_ids' => ['array'],
            'parent_ids.*' => ['integer', 'exists:categories,id'],
        ])->validate()['parent_ids'];

        $user = $request->user();
        $isAdmin = $user->hasRole('Admin');
        $viewableIds = $isAdmin ? [] : $user->getViewableCategoryIds();
        $query = Category::with('parent')->withCount('documents');

        if (! $isAdmin) {
            $query->whereIn('id', $viewableIds);
        }

        if (! empty($parentIds)) {
            $query->whereIn('parent_id', $parentIds);
        }

        $paginated = $query->paginate($perPage)
            ->appends($request->except('page'));

        $categories = collect($paginated->items())->map(function ($category) use ($user, $isAdmin) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent_title' => $category->parent?->title,
                'documents_count' => $category->documents_count ?? 0,
                'user_permissions' => $isAdmin ? ['manage'] : $category->users()
                    ->where('user_id', $user->id)
                    ->pluck('permission')
                    ->all(),
            ];
        })->toArray();

        return Inertia::render('Category/CategoryIndex', [
            'categories' => $categories,
            // The list can be paginated, so provide every category separately
            // for the nested-category selector.
            'parents' => Category::query()
                ->when(! $isAdmin, fn ($query) => $query->whereIn('id', $viewableIds))
                ->orderBy('title')
                ->get(['id', 'title', 'parent_id']),
            'selectedParentIds' => $parentIds,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Category::class);

        return Inertia::render('Category/CategoryCreate', [
            'parents' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ]);

        $this->service->store($request);

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function show(Request $request, Category $category)
    {
        $this->authorize('view', $category);

        $documents = Document::where('category_id', $category->id)->paginate(10);
        $subcategories = Category::where('parent_id', $category->id)
            ->withCount('documents')
            ->get();
        $allCategories = Category::query()
            ->whereNull('parent_id')
            ->when(! $request->user()->hasRole('Admin'), fn ($query) => $query->whereIn('id', $request->user()->getViewableCategoryIds()))
            ->with('childrenRecursive')
            ->get();

        return Inertia::render('Category/CategoryDetails', [
            'category' => $category,
            'categories' => $allCategories,
            'documents' => $documents->items(),
            'subcategories' => $subcategories,
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    public function dashboard(Request $request, Category $category)
    {
        $this->authorize('view', $category);

        $user = $request->user();
        $documents = Document::where('category_id', $category->id)->paginate(10);

        return Inertia::render('Category/CategoryDashboard', [
            'category' => [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'documents_count' => $category->documents()->count(),
            ],
            'documents' => $documents->items(),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
            'user_permissions' => $user->hasRole('Admin') ? ['manage'] : $category->users()
                ->where('user_id', $user->id)
                ->pluck('permission')
                ->all(),
        ]);
    }

    public function edit(string $id)
    {
        $category = $this->service->find($id);
        $this->authorize('update', $category);

        return Inertia::render('Category/CategoryUpdate', [
            'category' => $category,
            'parents' => Category::whereNotIn('id', $this->categoryAndDescendantIds((int) $id))
                ->orderBy('title')
                ->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $category = $this->service->find($id);
        $this->authorize('update', $category);

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
        $category = $this->service->find($id);
        $this->authorize('delete', $category);

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
            $category = Category::findOrFail($id);
            $this->authorize('delete', $category);
            $this->service->delete($id);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Selected categories deleted successfully!');
    }

    /**
     * Return a category and every nested child, to prevent circular trees.
     * Uses a batch iterative approach compatible with all databases.
     *
     * @return array<int>
     */
    private function categoryAndDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $pending = [$categoryId];

        while (! empty($pending)) {
            $found = Category::whereIn('parent_id', $pending)
                ->pluck('id')
                ->all();

            $pending = [];

            foreach ($found as $id) {
                if (! in_array($id, $ids)) {
                    $ids[] = $id;
                    $pending[] = $id;
                }
            }
        }

        return $ids;
    }
}
