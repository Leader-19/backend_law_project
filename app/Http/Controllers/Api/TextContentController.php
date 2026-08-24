<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\TextContent;
use Illuminate\Http\Request;

class TextContentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', '');
        $categoryId = $request->get('category_id');

        $query = TextContent::with('category:id,title');

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $textContent = TextContent::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Text content created successfully.',
            'data' => $textContent->load('category:id,title'),
        ], 201);
    }

    public function show(string $id)
    {
        $textContent = TextContent::with('category:id,title')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $textContent,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $textContent = TextContent::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $textContent->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Text content updated successfully.',
            'data' => $textContent->fresh()->load('category:id,title'),
        ]);
    }

    public function destroy(string $id)
    {
        $textContent = TextContent::findOrFail($id);
        $textContent->delete();

        return response()->noContent();
    }
}
