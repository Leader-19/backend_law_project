<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $leaderboard = QuizAttempt::query()
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('MAX(score) as best_score'),
                DB::raw('SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as quizzes_passed'),
                DB::raw('ROUND(AVG(score), 1) as average_score')
            )
            ->whereHas('quiz', function ($q) {
                $q->where('is_active', true);
            })
            ->groupBy('user_id')
            ->orderByDesc('best_score')
            ->orderByDesc('average_score')
            ->limit(50)
            ->get()
            ->map(function ($entry, $index) {
                $entry->rank = $index + 1;
                $entry->user = \App\Models\User::select('id', 'name', 'avatar')->find($entry->user_id);
                return $entry;
            })
            ->filter(fn ($entry) => $entry->user !== null)
            ->values();

        // Find current user's rank
        $userRank = null;
        $userStats = null;

        $userEntry = $leaderboard->firstWhere('user_id', $user->id);
        if ($userEntry) {
            $userRank = $userEntry->rank;
            $userStats = $userEntry;
        } else {
            // User has no attempts yet
            $userStats = (object) [
                'total_attempts' => 0,
                'best_score' => 0,
                'quizzes_passed' => 0,
                'average_score' => 0,
            ];
        }

        return response()->json([
            'status' => 'success',
            'leaderboard' => $leaderboard,
            'user_rank' => $userRank,
            'user_stats' => $userStats,
        ]);
    }
}