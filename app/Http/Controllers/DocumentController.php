<?php

namespace App\Http\Controllers;

use App\Http\Requests\Doc\DocumentRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Services\Documents\DocumentsService;
use App\Services\DocumentLimitService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

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
        $search = (string) $request->input('search', '');
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
        app(DocumentLimitService::class)->ensureCanCreate($request->user());

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
            'doc_upload' => ['required', 'array', 'min:1', 'max:50'],
            'doc_upload.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx', 'max:2097152'], // 2 GB max (KB)
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $this->authorize('create', [Document::class, $category]);
        app(DocumentLimitService::class)->ensureCanCreate($request->user(), count($validated['doc_upload']));
        $this->ensureBatchDocumentCapacity(count($validated['doc_upload']));

        foreach ($validated['doc_upload'] as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                return back()->withErrors(['doc_upload' => 'One or more files failed to upload. Please try again.'])->withInput();
            }
        }

        $result = $this->service->storeBatch($validated);
        $count = $result['count'];
        $failures = $result['failures'];

        if ($count === 0 && count($failures) > 0) {
            $failedNames = collect($failures)->pluck('file')->implode(', ');

            return back()->withErrors([
                'doc_upload' => "All uploads failed: {$failedNames}",
            ])->withInput();
        }

        $flash = "{$count} document(s) imported successfully!";
        if (count($failures) > 0) {
            $failedNames = collect($failures)->pluck('file')->implode(', ');
            $flash .= ' ('.count($failures)." failed: {$failedNames})";
        }

        ActivityLog::record('documents_batch_imported', "{$count} documents imported", $category, [
            'requested_count' => count($validated['doc_upload']),
            'imported_count' => $count,
            'failed_count' => count($failures),
        ]);

        return redirect()->route('documents.index')
            ->with('success', $flash);
    }

    public function batchStoreZip(Request $request)
    {
        $validated = $request->validate([
            'zip_file' => ['required', 'file', 'mimetypes:application/zip,application/x-zip-compressed,application/x-zip,multipart/x-zip', 'max:524288'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $this->authorize('create', [Document::class, $category]);
        $this->ensureBatchDocumentCapacity($this->countImportableZipFiles($validated['zip_file']));

        $zipFile = $validated['zip_file'];

        if ($zipFile->getError() !== UPLOAD_ERR_OK) {
            return back()->withErrors(['zip_file' => 'The ZIP file failed to upload. Please try again.'])->withInput();
        }

        $result = $this->service->storeBatchZip($zipFile, $validated['category_id'], $validated['description']);
        $count = $result['count'];
        $failures = $result['failures'];
        $skipped = $result['skipped'];

        if ($count === 0 && count($failures) > 0) {
            $failedNames = collect($failures)->pluck('file')->implode(', ');

            return back()->withErrors([
                'zip_file' => "All uploads failed: {$failedNames}",
            ])->withInput();
        }

        $flash = "{$count} document(s) imported from ZIP successfully!";
        if (count($failures) > 0) {
            $failedNames = collect($failures)->pluck('file')->implode(', ');
            $flash .= ' ('.count($failures)." failed: {$failedNames})";
        }
        if ($skipped > 0) {
            $flash .= " ({$skipped} unsupported file(s) skipped)";
        }

        ActivityLog::record('documents_zip_imported', "{$count} documents imported from ZIP", $category, [
            'imported_count' => $count,
            'failed_count' => count($failures),
            'skipped_count' => $skipped,
        ]);

        return redirect()->route('documents.index')
            ->with('success', $flash);
    }

    private function ensureBatchDocumentCapacity(int $incomingCount): void
    {
        $user = request()->user();

        if (! $user || $user->hasRole('Admin')) {
            return;
        }

        $plans = $user->activeSubscriptions()->with('plan')->get()->pluck('plan')->filter();
        if ($plans->isEmpty()) {
            throw ValidationException::withMessages([
                'doc_upload' => ['An active subscription is required to import documents.'],
            ]);
        }

        $hasUnlimitedPlan = $plans->contains(fn ($plan) => ! $plan->max_documents);
        $limit = $plans->sum('max_documents');
        $currentCount = $user->documents()->count();

        if (! $hasUnlimitedPlan && $currentCount + $incomingCount > $limit) {
            throw ValidationException::withMessages([
                'doc_upload' => ["This batch would exceed your document limit of {$limit}. You currently have {$currentCount} document(s)."],
            ]);
        }
    }

    private function countImportableZipFiles($zipFile): int
    {
        $zip = new \ZipArchive;
        if ($zip->open($zipFile->getRealPath()) !== true) {
            throw ValidationException::withMessages(['zip_file' => ['Unable to open the ZIP file.']]);
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        $count = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);
            if ($entry && $entry['size'] > 0 && in_array(strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION)), $allowedExtensions, true)) {
                $count++;
            }
        }
        $zip->close();

        if ($count === 0) {
            throw ValidationException::withMessages(['zip_file' => ['The ZIP file contains no supported documents.']]);
        }
        if ($count > 100) {
            throw ValidationException::withMessages(['zip_file' => ['A ZIP import may contain at most 100 supported documents.']]);
        }

        return $count;
    }
}
