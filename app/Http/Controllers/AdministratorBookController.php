<?php

namespace App\Http\Controllers;

use App\Models\AdministratorBook;
use App\Models\AdministratorBookCategory;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdministratorBookController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $categoryId = $request->get('category_id');

        $query = AdministratorBook::with(['category', 'subscriptionPlans'])
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('administrator_book_category_id', $categoryId);
        }

        $books = $query->paginate(10)->withQueryString();

        return Inertia::render('AdministratorBook/Index', [
            'books' => $books->items(),
            'pagination' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
            'categories' => AdministratorBookCategory::orderBy('name')->get(),
            'search' => $search,
            'selectedCategoryId' => $categoryId,
        ]);
    }

    public function create()
    {
        return Inertia::render('AdministratorBook/Create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateBook($request);

        $book = AdministratorBook::create([
            ...$validated,
            'file_path' => $this->storeFile($request),
        ]);

        $book->subscriptionPlans()->sync($validated['plan_ids'] ?? []);

        return redirect()->route('administrator-books.index')
            ->with('success', 'Administrator book created successfully!');
    }

    public function show(AdministratorBook $administratorBook)
    {
        $user = auth()->user();

        if (! $administratorBook->isAccessibleBy($user)) {
            $message = $administratorBook->subscriptionPlans()->exists()
                ? 'This book requires an active subscription. Please upgrade your plan to read it.'
                : 'You need an active subscription to read this book.';

            return redirect()->route('billing.pricing')->with('error', $message);
        }

        return Inertia::render('AdministratorBook/Show', [
            'book' => [
                ...$administratorBook->only(['id', 'title', 'name', 'description', 'file_path']),
                'file_url' => $administratorBook->file_url,
                'category' => $administratorBook->category?->only(['id', 'name']),
                'plans' => $administratorBook->subscriptionPlans->map(
                    fn (SubscriptionPlan $plan) => ['id' => $plan->id, 'name' => $plan->name]
                )->all(),
            ],
        ]);
    }

    public function edit(AdministratorBook $administratorBook)
    {
        return Inertia::render('AdministratorBook/Update', [
            ...$this->formData(),
            'book' => [
                ...$administratorBook->only([
                    'id', 'title', 'name', 'description',
                    'administrator_book_category_id', 'file_path',
                ]),
                'file_url' => $administratorBook->file_url,
                'plan_ids' => $administratorBook->subscriptionPlans->pluck('id')->all(),
            ],
        ]);
    }

    public function update(Request $request, AdministratorBook $administratorBook)
    {
        $validated = $this->validateBook($request);

        $administratorBook->update([
            ...$validated,
            'file_path' => $this->storeFile($request) ?? $administratorBook->file_path,
        ]);

        $administratorBook->subscriptionPlans()->sync($validated['plan_ids'] ?? []);

        return redirect()->route('administrator-books.index')
            ->with('success', 'Administrator book updated successfully!');
    }

    public function destroy(AdministratorBook $administratorBook)
    {
        $this->deleteFile($administratorBook->file_path);
        $administratorBook->delete();

        return redirect()->route('administrator-books.index')
            ->with('success', 'Administrator book deleted successfully!');
    }

    /** Stream the uploaded file for reading (subscription-gated). */
    public function read(AdministratorBook $administratorBook)
    {
        $user = auth()->user();

        if (! $administratorBook->isAccessibleBy($user)) {
            $message = $administratorBook->subscriptionPlans()->exists()
                ? 'This book requires an active subscription. Please upgrade your plan to read it.'
                : 'You need an active subscription to read this book.';

            return redirect()->route('billing.pricing')->with('error', $message);
        }

        abort_unless($administratorBook->file_path, 404, 'This book has no attached file.');

        return Storage::disk('public')->download($administratorBook->file_path);
    }

    protected function validateBook(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'administrator_book_category_id' => [
                'required',
                'integer',
                'exists:administrator_book_categories,id',
            ],
            'file' => ['nullable', 'file', 'max:20480'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['integer', 'exists:subscription_plans,id'],
        ]);
    }

    protected function storeFile(Request $request): ?string
    {
        if (! $request->hasFile('file')) {
            return null;
        }

        // Remove the previous file on replace
        if ($request->route('administrator_book')) {
            $this->deleteFile($request->route('administrator_book')->file_path);
        }

        return $request->file('file')->store('administrator-books', 'public');
    }

    protected function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function formData(): array
    {
        return [
            'categories' => AdministratorBookCategory::orderBy('name')->get(),
            'plans' => SubscriptionPlan::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'currency', 'is_active']),
        ];
    }
}
