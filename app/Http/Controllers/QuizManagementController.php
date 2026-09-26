<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuizManagementController extends Controller
{
    private function getCategories()
    {
        return Cache::remember('categories.list.simple', now()->addMinutes(30), function () {
            return Category::orderBy('title')->get(['id', 'title', 'parent_id']);
        });
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $quizzes = Quiz::query()
            ->with([
                'category:id,title',
                'creator:id,name',
            ])
            ->withCount(['questions', 'attempts'])
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Quizzes/Admin/Index', [
            'quizzes' => $quizzes,
            'search' => $search,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Quizzes/Admin/Create', [
            'categories' => $this->getCategories(),
        ]);
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
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

        $quiz = Quiz::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'max_attempts' => $validated['max_attempts'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Quiz created successfully.',
                'quiz' => $quiz->load('category:id,title'),
            ], 201);
        }

        return redirect()->route('quizzes-management.index')
            ->with('success', 'Quiz created successfully.')
            ->with('quiz_id', $quiz->id);
    }

    public function edit(Quiz $quiz): Response
    {
        $quiz->load(['questions.options', 'category:id,title']);

        return Inertia::render('Quizzes/Admin/Edit', [
            'quiz' => $quiz,
            'categories' => $this->getCategories(),
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

    public function storeQuestion(Request $request, Quiz $quiz): RedirectResponse|\Illuminate\Http\JsonResponse
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

            $options = [];
            foreach ($validated['options'] as $index => $option) {
                $options[] = [
                    'question_id' => $question->id,
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct'],
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            QuizOption::insert($options);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Question added successfully.',
                    'question' => $question->load('options'),
                ], 201);
            }

            return back()->with('success', 'Question added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to add question.',
                ], 422);
            }

            return back()->with('error', 'Failed to add question.');
        }
    }

    public function updateQuestion(Request $request, Quiz $quiz, QuizQuestion $question): RedirectResponse|\Illuminate\Http\JsonResponse
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

            $question->options()->delete();

            $options = [];
            foreach ($validated['options'] as $index => $option) {
                $options[] = [
                    'question_id' => $question->id,
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct'],
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            QuizOption::insert($options);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Question updated successfully.',
                    'question' => $question->load('options'),
                ]);
            }

            return back()->with('success', 'Question updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update question.',
                ], 422);
            }

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
        $quiz->load('category:id,title');

        $attempts = $quiz->attempts()
            ->with('user:id,name,email')
            ->latest('completed_at')
            ->paginate(20);

        $statsRow = $quiz->attempts()
            ->selectRaw('
                COUNT(*) as total_attempts,
                ROUND(AVG(score), 1) as average_score,
                SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count
            ')
            ->first();

        $total = (int) ($statsRow->total_attempts ?? 0);

        $stats = [
            'total_attempts' => $total,
            'average_score' => (float) ($statsRow->average_score ?? 0),
            'pass_rate' => $total > 0
                ? round(($statsRow->passed_count / $total) * 100, 1)
                : 0,
        ];

        return Inertia::render('Quizzes/Admin/Attempts', [
            'quiz' => $quiz,
            'attempts' => $attempts,
            'stats' => $stats,
        ]);
    }
}
