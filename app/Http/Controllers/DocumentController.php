<?php

namespace App\Http\Controllers;

use App\Http\Requests\Doc\DocumentRequest;
use App\Models\Category;
use App\Models\Document;
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

        $searchType = $request->get('search_type', 'all');

        $paginated = $this->service->getPaginated($perPage, $page, $search, $categoryIds, $searchType);

        return Inertia::render('Documents/DocumentIndex', [
            'documents' => $paginated->items(),
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
            'selectedCategoryIds' => $categoryIds,
            'searchType' => $searchType,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
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
        $category = Category::findOrFail($request->category_id);
        $this->authorize('create', [Document::class, $category]);

        $this->service->store($request);

        return redirect()->route('documents.index')
            ->with('success', 'Document created successfully!');
    }

    public function show(string $id)
    {
        $document = $this->service->find($id);
        $this->authorize('view', $document);

        return Inertia::render('Documents/DocumentDetails', [
            'document' => $document,
        ]);
    }

    public function edit(string $id)
    {
        $document = $this->service->find($id);
        $this->authorize('update', $document);

        return Inertia::render('Documents/DocumentUpdate', [
            'document' => $document,
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function update(DocumentRequest $request, string $id)
    {
        $document = $this->service->find($id);
        $this->authorize('update', $document);

        $this->service->update($request, $id);

        return redirect()->route('documents.index')
            ->with('success', 'Document updated successfully!');
    }

    public function destroy(string $id)
    {
        $document = $this->service->find($id);
        $this->authorize('delete', $document);

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
            $document = $this->service->find($id);
            $this->authorize('delete', $document);
            $this->service->delete($id);
        }

        return redirect()->route('documents.index')
            ->with('success', 'Selected documents deleted successfully!');
    }

    public function batchCreate()
    {
        return Inertia::render('Documents/DocumentBatchCreate', [
            'categories' => Category::orderBy('title')->get(['id', 'title', 'parent_id']),
        ]);
    }

    public function batchStore(Request $request)
    {
        $validated = $request->validate([
            'doc_upload' => ['required', 'array', 'min:1'],
            'doc_upload.*' => ['file', 'max:6291456'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $this->authorize('create', [Document::class, $category]);

        foreach ($validated['doc_upload'] as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                return back()->withErrors(['doc_upload' => 'One or more files failed to upload. Please try again.'])->withInput();
            }
        }

        $count = $this->service->storeBatch($validated);

        return redirect()->route('documents.index')
            ->with('success', "{$count} documents imported successfully!");
    }

    public function batchStoreZip(Request $request)
    {
        $validated = $request->validate([
            'zip_file' => ['required', 'file', 'mimetypes:application/zip,application/x-zip-compressed,application/x-zip,multipart/x-zip', 'max:10240'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $this->authorize('create', [Document::class, $category]);

        $zipFile = $validated['zip_file'];

        if ($zipFile->getError() !== UPLOAD_ERR_OK) {
            return back()->withErrors(['zip_file' => 'The ZIP file failed to upload. Please try again.'])->withInput();
        }

        $count = $this->service->storeBatchZip($zipFile, $validated['category_id'], $validated['description']);

        return redirect()->route('documents.index')
            ->with('success', "{$count} documents imported from ZIP successfully!");
    }
}
