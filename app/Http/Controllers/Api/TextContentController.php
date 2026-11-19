<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\TextContent\TextContentRequest;
use App\Models\Category;
use App\Models\TextContent;
use Illuminate\Http\Request;

class TextContentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('Admin');
        $viewableIds = $isAdmin ? null : ($user
            ? $user->getViewableCategoryIds()
            : Category::where('access_type', 'public')->pluck('id')->all());

        $perPage = $request->get('per_page', 15);

        if (! $isAdmin && empty($viewableIds)) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => (int) $perPage,
                    'total' => 0,
                ],
            ]);
        }

        $search = $request->get('search', '');
        $categoryId = $request->get('category_id');

        $query = TextContent::with('category:id,title')
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
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(TextContentRequest $request)
    {
        $category = Category::findOrFail($request->validated('category_id'));
        $this->authorize('create', [TextContent::class, $category]);

        $textContent = TextContent::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Text content created successfully.',
            'data' => $textContent->load('category:id,title'),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $textContent = TextContent::with('category:id,title,access_type,parent_id')->findOrFail($id);
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('Admin');

        $allowed = $isAdmin
            || ($user && $textContent->category && $user->hasCategoryPermission('view', $textContent->category))
            || (! $user && $textContent->category && $textContent->category->access_type === 'public');

        if (! $allowed) {
            return response()->json(['status' => 'error', 'message' => 'You do not have access to view this content.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $textContent,
        ]);
    }

    public function update(TextContentRequest $request, string $id)
    {
        $textContent = TextContent::findOrFail($id);
        $this->authorize('update', $textContent);
        $this->authorizeCategoryMove($request, $textContent->category_id);

        $textContent->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Text content updated successfully.',
            'data' => $textContent->fresh()->load('category:id,title'),
        ]);
    }

    public function destroy(string $id)
    {
        $textContent = TextContent::findOrFail($id);
        $this->authorize('delete', $textContent);

        $textContent->delete();

        return response()->noContent();
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
