<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\UserAnswer;
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

        return Inertia::render('Quizzes/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function show(Request $request, Quiz $quiz): Response
    {
        $user = $request->user();

        $quiz->load(['category', 'questions.options']);

        $attempts = $quiz->attemptsForUser($user)
            ->latest('completed_at')
            ->get();

        return Inertia::render('Quizzes/Show', [
            'quiz' => $quiz,
            'attempts' => $attempts,
            'canAttempt' => $quiz->canUserAttempt($user),
        ]);
    }

    public function take(Request $request, Quiz $quiz): Response
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

    public function submit(Request $request, Quiz $quiz): \Illuminate\Http\RedirectResponse
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

            foreach ($request->input('answers') as $answer) {
                $option = QuizOption::with('question')->find($answer['option_id']);
                $isCorrect = $option?->is_correct ?? false;

                if ($isCorrect) {
                    $correctCount++;
                }

                UserAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $answer['question_id'],
                    'option_id' => $answer['option_id'],
                    'is_correct' => $isCorrect,
                ]);
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
        $user = $request->user();

        // Ensure user can only view their own attempts
        abort_unless($attempt->user_id === $user->id, 403);

        $attempt->load([
            'quiz.category',
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
        $user = $request->user();

        $attempts = $user->quizAttempts()
            ->with('quiz.category')
            ->latest('completed_at')
            ->paginate(12);

        return Inertia::render('Quizzes/MyAttempts', [
            'attempts' => $attempts,
        ]);
    }
}
