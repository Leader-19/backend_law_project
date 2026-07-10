<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryManagementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $categoryId = $request->get('category_id');

        $categories = Category::withCount('documents')->get();

        $documents = Document::when($categoryId, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($search, function ($query, $search) {
                $query->where('doc_name', 'like', "%{$search}%")
                    ->orWhere('doc_title', 'like', "%{$search}%");
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return Inertia::render('Category/CategoryManagement', [
            'categories' => $categories,
            'documents' => $documents->items(),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ]
        ]);
    }
}