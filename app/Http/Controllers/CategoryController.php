<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Services\Categories\CategoriesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoriesService $service
    ) {}

    /**
     * Cached parent list for selectors.
     */
    private function getParents(?array $viewableIds = null)
    {
        $cacheKey = $viewableIds === null
            ? 'categories.parents.admin'
            : 'categories.parents.'.md5(implode(',', $viewableIds));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($viewableIds) {
            return Category::query()
                ->when($viewableIds !== null, fn ($q) => $q->whereIn('id', $viewableIds))
                ->orderBy('title')
                ->get(['id', 'title', 'parent_id']);
        });
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $perPage = min(max((int) $request->get('per_page', 10), 5), 50);
        $parentIds = $request->input('parent_ids', []);
        $parentIds = is_array($parentIds) ? $parentIds : [];

        $parentIds = validator(['parent_ids' => $parentIds], [
            'parent_ids' => ['array'],
            'parent_ids.*' => ['integer', 'exists:categories,id'],
        ])->validate()['parent_ids'];

        $user = $request->user();
        $isAdmin = $user->hasRole('Admin');
        $viewableIds = $isAdmin ? null : $user->getViewableCategoryIds();

        $query = Category::query()
            ->with('parent:id,title')
            ->withCount('documents');

        if (! $isAdmin) {
            $query->whereIn('id', $viewableIds);
        }

        if (! empty($parentIds)) {
            $query->whereIn('parent_id', $parentIds);
        }

        $paginated = $query->orderBy('title')
            ->paginate($perPage)
            ->appends($request->except('page'));

        // Avoid N+1: load current user's permissions in one query
        $userPermissions = [];
        if (! $isAdmin) {
            $categoryIds = collect($paginated->items())->pluck('id')->all();

            $userPermissions = $user->categoryPermissions()
                ->whereIn('category_id', $categoryIds)
                ->get()
                ->groupBy('pivot.category_id')
                ->map(fn ($rows) => $rows->pluck('pivot.permission')->all())
                ->toArray();
        }

        $categories = collect($paginated->items())->map(function ($category) use ($isAdmin, $userPermissions) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent_title' => $category->parent?->title,
                'documents_count' => $category->documents_count ?? 0,
                'user_permissions' => $isAdmin
                    ? ['manage']
                    : ($userPermissions[$category->id] ?? ['view']),
            ];
        })->toArray();

        return Inertia::render('Category/CategoryIndex', [
            'categories' => $categories,
            'parents' => $this->getParents($viewableIds),
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
            'parents' => $this->getParents(),
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

        $this->clearCategoryCaches();

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function show(Request $request, Category $category)
    {
        $this->authorize('view', $category);

        $user = $request->user();
        $isAdmin = $user->hasRole('Admin');
        $viewableIds = $isAdmin ? null : $user->getViewableCategoryIds();

        $documents = Document::where('category_id', $category->id)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        $subcategories = Category::where('parent_id', $category->id)
            ->withCount('documents')
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'parent_id']);

        // Only load tree when needed (and cache it)
        $allCategories = Cache::remember(
            $isAdmin ? 'categories.tree.admin' : 'categories.tree.'.$user->id,
            now()->addMinutes(15),
            function () use ($isAdmin, $viewableIds) {
                return Category::query()
                    ->whereNull('parent_id')
                    ->when(! $isAdmin, fn ($q) => $q->whereIn('id', $viewableIds))
                    ->with('childrenRecursive')
                    ->orderBy('title')
                    ->get();
            }
        );

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
        $isAdmin = $user->hasRole('Admin');

        $documents = Document::where('category_id', $category->id)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        // Use withCount instead of extra query
        $category->loadCount('documents');

        $userPermissions = $isAdmin
            ? ['manage']
            : $category->users()
                ->where('user_id', $user->id)
                ->pluck('permission')
                ->all();

        return Inertia::render('Category/CategoryDashboard', [
            'category' => [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'documents_count' => $category->documents_count,
            ],
            'documents' => $documents->items(),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
            'user_permissions' => $userPermissions,
        ]);
    }

    public function edit(string $id)
    {
        $category = $this->service->find($id);
        $this->authorize('update', $category);

        $excludeIds = $this->categoryAndDescendantIds((int) $id);

        return Inertia::render('Category/CategoryUpdate', [
            'category' => $category,
            'parents' => Category::whereNotIn('id', $excludeIds)
                ->orderBy('title')
                ->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $category = $this->service->find($id);
        $this->authorize('update', $category);

        $excludeIds = $this->categoryAndDescendantIds((int) $id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                Rule::notIn($excludeIds),
            ],
        ]);

        $this->service->update($request, $id);

        $this->clearCategoryCaches();

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(string $id)
    {
        $category = $this->service->find($id);
        $this->authorize('delete', $category);

        $this->service->delete($id);

        $this->clearCategoryCaches();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);

        $categories = Category::whereIn('id', $validated['ids'])->get();

        foreach ($categories as $category) {
            $this->authorize('delete', $category);
            $this->service->delete($category->id);
        }

        $this->clearCategoryCaches();

        return redirect()->route('categories.index')
            ->with('success', 'Selected categories deleted successfully!');
    }

    private function clearCategoryCaches(): void
    {
        Cache::forget('categories.list');
        Cache::forget('categories.parents.admin');
        // Clear user-specific caches (simple approach)
        Cache::flush(); // or use cache tags if available
    }

    /**
     * Return a category and every nested child (prevent circular trees).
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
                if (! in_array($id, $ids, true)) {
                    $ids[] = $id;
                    $pending[] = $id;
                }
            }
        }

        return $ids;
    }
}
