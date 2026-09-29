<?php

namespace App\Http\Controllers;

use App\Models\ReadingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadingHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $history = $request->user()
            ->readingHistory()
            ->with([
                'document:id,doc_name,doc_title,category_id,image',
                'document.category:id,title',
            ])
            ->latest('last_opened_at')
            ->paginate(12);

        return Inertia::render('ReadingHistory/Index', [
            'history' => $history,
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
        $totalPages = (int) $request->input('total_pages');
        $lastPage = (int) $request->input('last_page');

        $progress = $totalPages > 0
            ? round(($lastPage / $totalPages) * 100, 2)
            : 0;

        $history = ReadingHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'document_id' => $request->input('document_id'),
            ],
            [
                'last_page' => $lastPage,
                'total_pages' => $totalPages,
                'progress_percent' => $progress,
                'last_opened_at' => now(),
            ]
        );

        return response()->json([
            'progress_percent' => $history->progress_percent,
            'last_page' => $history->last_page,
        ]);
    }

    public function getProgress(Request $request, int $documentId): JsonResponse
    {
        $history = ReadingHistory::where('user_id', $request->user()->id)
            ->where('document_id', $documentId)
            ->first(['last_page', 'total_pages', 'progress_percent']);

        return response()->json([
            'last_page' => $history?->last_page ?? 1,
            'total_pages' => $history?->total_pages ?? 1,
            'progress_percent' => $history?->progress_percent ?? 0,
        ]);
    }
}
