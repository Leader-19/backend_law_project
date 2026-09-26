<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $viewableIds = $user->getViewableCategoryIds();

        $quizzes = Quiz::whereIn('category_id', $viewableIds)
            ->where('is_active', true)
            ->with('category')
            ->withCount('attempts')
            ->latest()
            ->paginate(12);

        // Annotate with user's attempts
        $quizzes->getCollection()->transform(function ($quiz) use ($user) {
            $quiz->user_attempts = $quiz->attemptsForUser($user)->count();
            $quiz->user_best_score = $quiz->attemptsForUser($user)->max('score');
            $quiz->user_passed = $quiz->attemptsForUser($user)->where('passed', true)->exists();
            $quiz->can_attempt = $quiz->canUserAttempt($user);

            return $quiz;
        });

        return response()->json([
            'status' => 'success',
            'data' => $quizzes->items(),
            'pagination' => [
                'current_page' => $quizzes->currentPage(),
                'last_page' => $quizzes->lastPage(),
                'per_page' => $quizzes->perPage(),
                'total' => $quizzes->total(),
            ],
        ]);
    }

    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $request->user();

        $quiz->load(['category', 'questions.options']);

        $attempts = $quiz->attemptsForUser($user)
            ->latest('completed_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'quiz' => $quiz,
            'attempts' => $attempts,
            'can_attempt' => $quiz->canUserAttempt($user),
        ]);
    }

    public function take(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $request->user();

        if (! $quiz->canUserAttempt($user)) {
            return response()->json(['message' => 'You cannot attempt this quiz.'], 403);
        }

        $quiz->load(['questions.options']);

        return response()->json([
            'status' => 'success',
            'quiz' => $quiz,
        ]);
    }

    public function submit(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $request->user();

        if (! $quiz->canUserAttempt($user)) {
            return response()->json(['message' => 'You cannot attempt this quiz.'], 403);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.option_id' => 'required|exists:quiz_options,id',
        ]);

        // Group answers by question
        $answersByQuestion = [];
        foreach ($request->input('answers') as $answer) {
            $answersByQuestion[$answer['question_id']][] = $answer['option_id'];
        }

        DB::beginTransaction();

        try {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            $correctCount = 0;
            $totalQuestions = $quiz->questions()->count();

            foreach ($quiz->questions()->with('options')->get() as $question) {
                $selectedOptionIds = $answersByQuestion[$question->id] ?? [];
                $correctOptionIds = $question->options->where('is_correct', true)->pluck('id')->all();

                // Question is correct if user selected ALL correct options and NO incorrect ones
                $isQuestionCorrect = ! empty($correctOptionIds)
                    && empty(array_diff($correctOptionIds, $selectedOptionIds))
                    && empty(array_diff($selectedOptionIds, $correctOptionIds));

                if ($isQuestionCorrect) {
                    $correctCount++;
                }

                // Save each selected answer
                foreach ($selectedOptionIds as $optionId) {
                    $option = $question->options->firstWhere('id', $optionId);
                    UserAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'option_id' => $optionId,
                        'is_correct' => $option?->is_correct ?? false,
                    ]);
                }
            }

            $score = $totalQuestions > 0
                ? round(($correctCount / $totalQuestions) * 100)
                : 0;

            $passed = $score >= $quiz->passing_score;

            $attempt->update([
                'score' => $score,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'passed' => $passed,
            ]);

            // Generate certificate if passed
            if ($passed) {
                Certificate::create([
                    'user_id' => $user->id,
                    'quiz_id' => $quiz->id,
                    'attempt_id' => $attempt->id,
                    'certificate_number' => Certificate::generateCertificateNumber(),
                    'score' => $score,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'attempt' => $attempt->load(['quiz.category', 'answers.question', 'answers.option', 'certificate']),
                'message' => $passed
                    ? 'Congratulations! You passed the quiz!'
                    : 'You did not pass this time. You can try again.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'An error occurred while submitting your answers.'], 500);
        }
    }

    public function result(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $user = $request->user();

        // Ensure user can only view their own attempts
        abort_unless($attempt->user_id === $user->id, 403);

        $attempt->load([
            'quiz.category',
            'answers.question',
            'answers.option',
            'certificate',
        ]);

        return response()->json([
            'status' => 'success',
            'attempt' => $attempt,
        ]);
    }

    public function myAttempts(Request $request): JsonResponse
    {
        $user = $request->user();

        $attempts = $user->quizAttempts()
            ->with('quiz.category')
            ->latest('completed_at')
            ->paginate(12);

        return response()->json([
            'status' => 'success',
            'data' => $attempts->items(),
            'pagination' => [
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'per_page' => $attempts->perPage(),
                'total' => $attempts->total(),
            ],
        ]);
    }
}
