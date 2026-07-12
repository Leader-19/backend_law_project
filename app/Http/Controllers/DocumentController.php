<?php

namespace App\Http\Controllers;

use App\Http\Requests\Doc\DocumentRequest;
use App\Models\Category;
use App\Services\Documents\DocumentsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentController extends Controller
{
    protected $service;

    public function __construct(DocumentsService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $categoryIds = $request->input('category_ids', []);
        $categoryIds = is_array($categoryIds) ? $categoryIds : [];

        if ($categoryIds === [] && $request->filled('category_id')) {
            $categoryIds = [$request->input('category_id')];
        }

        $validated = validator(['category_ids' => $categoryIds], [
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ])->validate();
        $categoryIds = $validated['category_ids'];

        $paginated = $this->service->getPaginated($perPage, $page, $search, $categoryIds);

        return Inertia::render('Documents/DocumentIndex', [
            'documents' => $paginated->items(),
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
            'selectedCategoryIds' => $categoryIds,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Documents/DocumentCreate', [
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function store(DocumentRequest $request)
    {
        $this->service->store($request);

        return redirect()->route('documents.index')
            ->with('success', 'Document created successfully!');
    }

    public function show(string $id)
    {
        return Inertia::render('Documents/DocumentDetails', [
            'document' => $this->service->find($id),
        ]);
    }

    public function edit(string $id)
    {
        return Inertia::render('Documents/DocumentUpdate', [
            'document' => $this->service->find($id),
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function update(DocumentRequest $request, string $id)
    {
        $this->service->update($request, $id);

        return redirect()->route('documents.index')
            ->with('success', 'Document updated successfully!');
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:documents,id'],
        ]);

        foreach ($validated['ids'] as $id) {
            $this->service->delete($id);
        }

        return redirect()->route('documents.index')
            ->with('success', 'Selected documents deleted successfully!');
    }
}
