<?php

namespace App\Http\Controllers;

use App\Models\UserLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->library()->with('category');

        // Apply sorting
        // Note: the BelongsToMany library() relationship already JOINs documents
        // with user_library, so we can reference documents.* columns directly.
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest('user_library.created_at');
                break;
            case 'title_asc':
                $query->orderBy('documents.doc_title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('documents.doc_title', 'desc');
                break;
            case 'category':
                $query->join('categories', 'documents.category_id', '=', 'categories.id')
                    ->orderBy('categories.title', 'asc');
                break;
            default: // newest
                $query->latest('user_library.created_at');
                break;
        }

        if ($search = $request->input('search')) {
            $query->whereHas('document', function ($q) use ($search) {
                $q->where('doc_name', 'like', "%{$search}%")
                    ->orWhere('doc_title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (($categoryId = $request->input('category_id')) !== null && $categoryId !== '') {
            if ($categoryId === '0') {
                // Uncategorized — documents with no category
                $query->where('document_id', function ($q) {
                    $q->select('id')
                        ->from('documents')
                        ->whereNull('category_id')
                        ->orWhere('category_id', 0);
                });
            } else {
                $query->where('document_id', function ($q) use ($categoryId) {
                    $q->select('id')
                        ->from('documents')
                        ->where('category_id', $categoryId);
                });
            }
        }

        $perPage = min((int) $request->input('per_page', 12), 50);
        $libraryItems = $query->paginate($perPage);

        // Get distinct categories with document counts from the user's full library.
        // Use DB::table() directly instead of the BelongsToMany relationship to
        // avoid extra pivot columns being added to SELECT (which breaks GROUP BY
        // under only_full_group_by mode).
        $categories = DB::table('user_library')
            ->join('documents', 'user_library.document_id', '=', 'documents.id')
            ->join('categories', 'documents.category_id', '=', 'categories.id')
            ->where('user_library.user_id', $user->id)
            ->select('categories.id', 'categories.title')
            ->selectRaw('COUNT(DISTINCT user_library.document_id) as document_count')
            ->groupBy('categories.id', 'categories.title')
            ->orderBy('categories.title')
            ->get();

        // Count uncategorized documents (no category or category_id = 0).
        // Use DB::table() directly to avoid extra pivot columns from BelongsToMany.
        $uncategorizedCount = DB::table('user_library')
            ->join('documents', 'user_library.document_id', '=', 'documents.id')
            ->where('user_library.user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('documents.category_id')
                    ->orWhere('documents.category_id', 0);
            })
            ->count();

        if ($uncategorizedCount > 0) {
            $categories->push((object) [
                'id' => '0',
                'title' => 'Uncategorized',
                'document_count' => $uncategorizedCount,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $libraryItems->items(),
            'categories' => $categories,
            'total' => $libraryItems->total(),
            'current_page' => $libraryItems->currentPage(),
            'last_page' => $libraryItems->lastPage(),
            'per_page' => $libraryItems->perPage(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id',
        ]);

        $user = $request->user();
        $documentId = $request->input('document_id');

        // Check if already in library
        $exists = UserLibrary::where('user_id', $user->id)
            ->where('document_id', $documentId)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'success',
                'message' => 'Document is already in your library.',
            ]);
        }

        UserLibrary::create([
            'user_id' => $user->id,
            'document_id' => $documentId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Document added to your library.',
        ]);
    }

    public function destroy(Request $request, int $documentId): JsonResponse
    {
        $user = $request->user();

        UserLibrary::where('user_id', $user->id)
            ->where('document_id', $documentId)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document removed from your library.',
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer|exists:documents,id',
        ]);

        $user = $request->user();
        $documentIds = $request->input('document_ids');

        $deleted = UserLibrary::where('user_id', $user->id)
            ->whereIn('document_id', $documentIds)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => "{$deleted} document(s) removed from your library.",
            'deleted_count' => $deleted,
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $user = $request->user();
        $documentId = $request->input('document_id');

        $inLibrary = UserLibrary::where('user_id', $user->id)
            ->where('document_id', $documentId)
            ->exists();

        return response()->json(['in_library' => $inLibrary]);
    }
}
