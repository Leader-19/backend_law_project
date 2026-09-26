<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = $user->readingHistory()
            ->with('document.category')
            ->latest('last_opened_at')
            ->paginate(12);

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
            'document_id' => 'required|exists:documents,id',
            'last_page' => 'required|integer|min:1',
            'total_pages' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        $progress = ($request->input('total_pages') > 0)
            ? round(($request->input('last_page') / $request->input('total_pages')) * 100, 2)
            : 0;

        $history = ReadingHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'document_id' => $request->input('document_id'),
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

    public function getProgress(Request $request, int $documentId): JsonResponse
    {
        $user = $request->user();

        $history = ReadingHistory::where('user_id', $user->id)
            ->where('document_id', $documentId)
            ->first();

        return response()->json([
            'status' => 'success',
            'last_page' => $history?->last_page ?? 1,
            'total_pages' => $history?->total_pages ?? 1,
            'progress_percent' => $history?->progress_percent ?? 0,
        ]);
    }
}
