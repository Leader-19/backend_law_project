<?php

namespace App\Http\Controllers;

use App\Models\AdministratorBook;
use App\Models\AdministratorBookCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdministratorBookCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $categories = AdministratorBookCategory::withCount('books')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('AdministratorBook/Categories', [
            'categories' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:administrator_book_categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        AdministratorBookCategory::create($validated);

        return redirect()->route('administrator-book-categories.index')
            ->with('success', 'Book category created successfully!');
    }

    public function update(Request $request, AdministratorBookCategory $administratorBookCategory)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:administrator_book_categories,name,'.$administratorBookCategory->id,
            ],
            'description' => ['nullable', 'string'],
        ]);

        $administratorBookCategory->update($validated);

        return redirect()->route('administrator-book-categories.index')
            ->with('success', 'Book category updated successfully!');
    }

    public function destroy(AdministratorBookCategory $administratorBookCategory)
    {
        $bookCount = $administratorBookCategory->books()->count();

        if ($bookCount > 0) {
            return redirect()->route('administrator-book-categories.index')
                ->with('error', "Cannot delete: {$bookCount} book(s) still use this category.");
        }

        $administratorBookCategory->delete();

        return redirect()->route('administrator-book-categories.index')
            ->with('success', 'Book category deleted successfully!');
    }
}
