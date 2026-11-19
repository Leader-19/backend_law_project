<?php

namespace App\Http\Controllers;

use App\Http\Requests\TextContent\TextContentRequest;
use App\Models\Category;
use App\Models\TextContent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TextContentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $categoryId = $request->get('category_id');

        $user = $request->user();
        $isAdmin = $user->hasRole('Admin');
        $viewableIds = $isAdmin ? null : $user->getViewableCategoryIds();

        $query = TextContent::with('category')
            ->when(! $isAdmin, fn ($q) => $q->whereIn('category_id', $viewableIds));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $paginated = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $quota = null;

        if ($user && ! $user->hasRole('Admin')) {
            $limit = $user->textContentLimit();
            if ($limit !== null) {
                $used = $user->textContents()->count();
                $quota = [
                    'used' => $used,
                    'limit' => $limit,
                    'remaining' => max(0, $limit - $used),
                ];
            }
        }

        return Inertia::render('TextContents/TextContentIndex', [
            'textContents' => $paginated->items(),
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'search' => $search,
            'selectedCategoryId' => $categoryId,
            'quota' => $quota,
        ]);
    }

    public function create()
    {
        return Inertia::render('TextContents/TextContentCreate', [
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function store(TextContentRequest $request)
    {
        $validated = $request->validated();

        $category = Category::findOrFail($validated['category_id']);
        $this->authorize('create', [TextContent::class, $category]);

        TextContent::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('text-contents.index')
            ->with('success', 'Text content created successfully!');
    }

    public function show(string $id)
    {
        $textContent = TextContent::with('category')->findOrFail($id);
        $this->authorize('view', $textContent);

        return Inertia::render('TextContents/TextContentShow', [
            'textContent' => $textContent,
        ]);
    }

    public function edit(string $id)
    {
        $textContent = TextContent::findOrFail($id);
        $this->authorize('update', $textContent);

        return Inertia::render('TextContents/TextContentUpdate', [
            'textContent' => $textContent,
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function update(TextContentRequest $request, string $id)
    {
        $textContent = TextContent::findOrFail($id);
        $this->authorize('update', $textContent);
        $this->authorizeCategoryMove($request, $textContent->category_id);

        $textContent->update($request->validated());

        return redirect()->route('text-contents.index')
            ->with('success', 'Text content updated successfully!');
    }

    public function destroy(string $id)
    {
        $textContent = TextContent::findOrFail($id);
        $this->authorize('delete', $textContent);
        $textContent->delete();

        return redirect()->route('text-contents.index')
            ->with('success', 'Text content deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:text_contents,id'],
        ]);

        foreach ($validated['ids'] as $id) {
            $textContent = TextContent::findOrFail($id);
            $this->authorize('delete', $textContent);
        }

        TextContent::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('text-contents.index')
            ->with('success', 'Selected text contents deleted successfully!');
    }

    /**
     * authorize('update', $textContent) only checks permission on the
     * record's current category. Since category_id is a plain editable
     * field, a user with edit rights on category A could otherwise move a
     * record into category B without ever being checked against B.
     */
    private function authorizeCategoryMove(TextContentRequest $request, ?int $currentCategoryId): void
    {
        $newCategoryId = (int) $request->validated('category_id');

        if ($newCategoryId === $currentCategoryId) {
            return;
        }

        $targetCategory = Category::findOrFail($newCategoryId);
        abort_unless(
            $request->user()->hasRole('Admin') || $request->user()->hasCategoryPermission('edit', $targetCategory),
            403,
            'You do not have permission to move this content into the selected category.'
        );
    }
}
