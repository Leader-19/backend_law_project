<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ReadingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $viewableCategoryIds = $user->getViewableCategoryIds();
        $history = $user->readingHistory()
            ->whereHas('document.category', fn ($q) => $q->whereIn('categories.id', $viewableCategoryIds))
            ->with('document.category')
            ->latest('last_opened_at')
            ->paginate(12);

        // Eloquent casts the primary key back to int on read regardless of
        // what's assigned to it, so the uuid swap has to happen on the
        // already-converted array, not on the live model attribute.
        $history->getCollection()->transform(function ($entry) {
            $array = $entry->toArray();
            if ($entry->document) {
                $array['document']['id'] = $entry->document->uuid;
            }

            return $array;
        });

        return response()->json([
            'status' => 'success',
            'data' => $history->items(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    public function updateProgress(Request $request): JsonResponse
    {
        $request->validate([
            'document_id' => 'required|string|exists:documents,uuid',
            'last_page' => 'required|integer|min:1',
            'total_pages' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $document = Document::with('category')->where('uuid', $request->input('document_id'))->firstOrFail();
        abort_unless($document->category && $user->hasCategoryPermission('view', $document->category), 403);

        $progress = ($request->input('total_pages') > 0)
            ? round(($request->input('last_page') / $request->input('total_pages')) * 100, 2)
            : 0;

        $history = ReadingHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'document_id' => $document->id,
            ],
            [
                'last_page' => $request->input('last_page'),
                'total_pages' => $request->input('total_pages'),
                'progress_percent' => $progress,
                'last_opened_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'progress_percent' => $history->progress_percent,
            'last_page' => $history->last_page,
        ]);
    }

    public function getProgress(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        $document->loadMissing('category');
        abort_unless($document->category && $user->hasCategoryPermission('view', $document->category), 403);

        $history = ReadingHistory::where('user_id', $user->id)
            ->where('document_id', $document->id)
            ->first();

        return response()->json([
            'status' => 'success',
            'last_page' => $history?->last_page ?? 1,
            'total_pages' => $history?->total_pages ?? 1,
            'progress_percent' => $history?->progress_percent ?? 0,
        ]);
    }
}
