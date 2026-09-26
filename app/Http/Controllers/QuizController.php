<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $viewableIds = $user->getViewableCategoryIds();

        $quizzes = Quiz::query()
            ->whereIn('category_id', $viewableIds)
            ->where('is_active', true)
            ->with('category:id,title')
            ->withCount('attempts')
            ->latest()
            ->paginate(12);

        $quizIds = $quizzes->getCollection()->pluck('id');

        // One query instead of N+1
        $userAttemptStats = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->selectRaw('
                quiz_id,
                COUNT(*) as attempts_count,
                MAX(score) as best_score,
                MAX(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as has_passed
            ')
            ->groupBy('quiz_id')
            ->get()
            ->keyBy('quiz_id');

        $quizzes->getCollection()->transform(function ($quiz) use ($user, $userAttemptStats) {
            $stats = $userAttemptStats->get($quiz->id);

            $quiz->user_attempts = (int) ($stats->attempts_count ?? 0);
            $quiz->user_best_score = $stats->best_score ?? null;
            $quiz->user_passed = (bool) ($stats->has_passed ?? false);
            $quiz->can_attempt = $quiz->canUserAttempt($user);

            return $quiz;
        });

        return Inertia::render('Quizzes/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function show(Request $request, Quiz $quiz): Response
    {
        $user = $request->user();

        $quiz->load([
            'category:id,title',
            'questions.options',
        ]);

        $attempts = $quiz->attemptsForUser($user)
            ->latest('completed_at')
            ->get();

        return Inertia::render('Quizzes/Show', [
            'quiz' => $quiz,
            'attempts' => $attempts,
            'canAttempt' => $quiz->canUserAttempt($user),
        ]);
    }

    public function take(Request $request, Quiz $quiz): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $quiz->canUserAttempt($user)) {
            return redirect()->route('quizzes.show', $quiz)
                ->with('error', 'You cannot attempt this quiz.');
        }

        $quiz->load(['questions.options']);

        return Inertia::render('Quizzes/Take', [
            'quiz' => $quiz,
        ]);
    }

    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        $user = $request->user();

        if (! $quiz->canUserAttempt($user)) {
            return back()->with('error', 'You cannot attempt this quiz.');
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.option_id' => 'required|exists:quiz_options,id',
        ]);

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

            // Load questions once
            $questions = $quiz->questions()->with('options')->get();
            $totalQuestions = $questions->count();
            $correctCount = 0;

            $userAnswers = [];

            foreach ($questions as $question) {
                $selectedOptionIds = $answersByQuestion[$question->id] ?? [];
                $correctOptionIds = $question->options
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->all();

                $isQuestionCorrect = ! empty($correctOptionIds)
                    && empty(array_diff($correctOptionIds, $selectedOptionIds))
                    && empty(array_diff($selectedOptionIds, $correctOptionIds));

                if ($isQuestionCorrect) {
                    $correctCount++;
                }

                foreach ($selectedOptionIds as $optionId) {
                    $option = $question->options->firstWhere('id', $optionId);
                    $userAnswers[] = [
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'option_id' => $optionId,
                        'is_correct' => $option?->is_correct ?? false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert answers
            if (! empty($userAnswers)) {
                UserAnswer::insert($userAnswers);
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

            return redirect()->route('quizzes.result', $attempt)
                ->with('success', $passed
                    ? 'Congratulations! You passed the quiz!'
                    : 'You did not pass this time. You can try again.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'An error occurred while submitting your answers.');
        }
    }

    public function result(Request $request, QuizAttempt $attempt): Response
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        $attempt->load([
            'quiz.category:id,title',
            'answers.question',
            'answers.option',
            'certificate',
        ]);

        return Inertia::render('Quizzes/Result', [
            'attempt' => $attempt,
        ]);
    }

    public function myAttempts(Request $request): Response
    {
        $attempts = $request->user()
            ->quizAttempts()
            ->with([
                'quiz:id,title,category_id',
                'quiz.category:id,title',
            ])
            ->latest('completed_at')
            ->paginate(12);

        return Inertia::render('Quizzes/MyAttempts', [
            'attempts' => $attempts,
        ]);
    }
}
