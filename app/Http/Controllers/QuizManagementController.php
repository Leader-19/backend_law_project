<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuizManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');

        $quizzes = Quiz::with(['category', 'creator'])
            ->withCount(['questions', 'attempts'])
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15);

        return Inertia::render('Quizzes/Admin/Index', [
            'quizzes' => $quizzes,
            'search' => $search,
        ]);
    }

    public function create(): Response
    {
        $categories = Category::orderBy('title')->get(['id', 'title', 'parent_id']);

        return Inertia::render('Quizzes/Admin/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'passing_score' => 'required|integer|min:0|max:100',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'max_attempts' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        Quiz::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'max_attempts' => $validated['max_attempts'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('quizzes-management.index')
            ->with('success', 'Quiz created successfully.');
    }

    public function edit(Quiz $quiz): Response
    {
        $quiz->load(['questions.options', 'category']);

        $categories = Category::orderBy('title')->get(['id', 'title', 'parent_id']);

        return Inertia::render('Quizzes/Admin/Edit', [
            'quiz' => $quiz,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'passing_score' => 'required|integer|min:0|max:100',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'max_attempts' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $quiz->update([
            ...$validated,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'max_attempts' => $validated['max_attempts'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $quiz->delete();

        return redirect()->route('quizzes-management.index')
            ->with('success', 'Quiz deleted successfully.');
    }

    public function storeQuestion(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'type' => 'required|string|in:multiple_choice,true_false',
            'options' => 'required|array|min:2',
            'options.*.option_text' => 'required|string|max:255',
            'options.*.is_correct' => 'required|boolean',
        ]);

        DB::beginTransaction();

        try {
            $maxOrder = $quiz->questions()->max('sort_order') ?? 0;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $validated['question'],
                'type' => $validated['type'],
                'sort_order' => $maxOrder + 1,
            ]);

            foreach ($validated['options'] as $index => $option) {
                QuizOption::create([
                    'question_id' => $question->id,
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct'],
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Question added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to add question.');
        }
    }

    public function updateQuestion(Request $request, Quiz $quiz, QuizQuestion $question): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'type' => 'required|string|in:multiple_choice,true_false',
            'options' => 'required|array|min:2',
            'options.*.id' => 'nullable|integer',
            'options.*.option_text' => 'required|string|max:255',
            'options.*.is_correct' => 'required|boolean',
        ]);

        abort_unless($question->quiz_id === $quiz->id, 404);

        DB::beginTransaction();

        try {
            $question->update([
                'question' => $validated['question'],
                'type' => $validated['type'],
            ]);

            // Delete existing options and recreate
            $question->options()->delete();

            foreach ($validated['options'] as $index => $option) {
                QuizOption::create([
                    'question_id' => $question->id,
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct'],
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Question updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update question.');
        }
    }

    public function destroyQuestion(Quiz $quiz, QuizQuestion $question): RedirectResponse
    {
        abort_unless($question->quiz_id === $quiz->id, 404);

        $question->delete();

        return back()->with('success', 'Question deleted successfully.');
    }

    public function reorderQuestions(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:quiz_questions,id',
        ]);

        foreach ($request->input('question_ids') as $index => $questionId) {
            QuizQuestion::where('id', $questionId)
                ->where('quiz_id', $quiz->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Questions reordered successfully.']);
    }

    public function attempts(Quiz $quiz): Response
    {
        $quiz->load('category');

        $attempts = $quiz->attempts()
            ->with('user')
            ->latest('completed_at')
            ->paginate(20);

        $stats = [
            'total_attempts' => $quiz->attempts()->count(),
            'average_score' => round($quiz->attempts()->avg('score') ?? 0, 1),
            'pass_rate' => $quiz->attempts()->count() > 0
                ? round(($quiz->attempts()->where('passed', true)->count() / $quiz->attempts()->count()) * 100, 1)
                : 0,
        ];

        return Inertia::render('Quizzes/Admin/Attempts', [
            'quiz' => $quiz,
            'attempts' => $attempts,
            'stats' => $stats,
        ]);
    }
}
