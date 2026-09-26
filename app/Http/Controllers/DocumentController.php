<?php

namespace App\Http\Controllers;

use App\Http\Requests\Doc\DocumentRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Services\DocumentLimitService;
use App\Services\Documents\DocumentsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentsService $service
    ) {}

    /**
     * Cached category list – reused in index / create / edit / batch.
     */
    private function getCategories()
    {
        return Cache::remember('categories.list', now()->addMinutes(30), function () {
            return Category::query()
                ->orderBy('title')
                ->get(['id', 'title', 'parent_id']);
        });
    }

    public function index(Request $request)
    {
        $perPage = min(max((int) $request->get('per_page', 10), 5), 50);
        $page = max((int) $request->get('page', 1), 1);
        $search = trim((string) $request->input('search', ''));
        $searchType = $request->get('search_type', 'all');

        $categoryIds = $request->input('category_ids', []);
        $categoryIds = is_array($categoryIds) ? $categoryIds : [];

        if ($categoryIds === [] && $request->filled('category_id')) {
            $categoryIds = [(int) $request->input('category_id')];
        }

        $validated = validator(['category_ids' => $categoryIds], [
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ])->validate();

        $categoryIds = $validated['category_ids'];

        $paginated = $this->service->getPaginated(
            $perPage,
            $page,
            $search,
            $categoryIds,
            $searchType
        );

        return Inertia::render('Documents/DocumentIndex', [
            'documents' => $paginated->items(),
            'categories' => $this->getCategories(),
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
            'categories' => $this->getCategories(),
        ]);
    }

    public function store(DocumentRequest $request)
    {
        $category = Category::findOrFail($request->category_id);
        $this->authorize('create', [Document::class, $category]);
        app(DocumentLimitService::class)->ensureCanCreate($request->user());

        $this->service->store($request);

        Cache::forget('categories.list');

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
            'categories' => $this->getCategories(),
        ]);
    }

    public function update(DocumentRequest $request, string $id)
    {
        $document = $this->service->find($id);
        $this->authorize('update', $document);

        $this->service->update($request, $id);

        Cache::forget('categories.list');

        return redirect()->route('documents.index')
            ->with('success', 'Document updated successfully!');
    }

    public function destroy(string $id)
    {
        $document = $this->service->find($id);
        $this->authorize('delete', $document);

        $this->service->delete($id);

        Cache::forget('categories.list');

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully!');
    }

    /**
     * Optimized bulk delete – load once, authorize, then delete.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct', 'exists:documents,id'],
        ]);

        $documents = Document::whereIn('id', $validated['ids'])->get();

        foreach ($documents as $document) {
            $this->authorize('delete', $document);
        }

        foreach ($documents as $document) {
            $this->service->delete($document->id);
        }

        Cache::forget('categories.list');

        return redirect()->route('documents.index')
            ->with('success', 'Selected documents deleted successfully!');
    }

    public function batchCreate()
    {
        return Inertia::render('Documents/DocumentBatchCreate', [
            'categories' => $this->getCategories(),
        ]);
    }

    public function batchStore(Request $request)
    {
        $validated = $request->validate([
            'doc_upload' => ['required', 'array', 'min:1', 'max:50'],
            'doc_upload.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx', 'max:2097152'],
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

        Cache::forget('categories.list');

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

        $result = $this->service->storeBatchZip(
            $zipFile,
            $validated['category_id'],
            $validated['description']
        );

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

        Cache::forget('categories.list');

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
            throw ValidationException::withMessages([
                'zip_file' => ['Unable to open the ZIP file.'],
            ]);
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        $count = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);

            if (
                $entry
                && $entry['size'] > 0
                && in_array(
                    strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION)),
                    $allowedExtensions,
                    true
                )
            ) {
                $count++;
            }
        }

        $zip->close();

        if ($count === 0) {
            throw ValidationException::withMessages([
                'zip_file' => ['The ZIP file contains no supported documents.'],
            ]);
        }

        if ($count > 100) {
            throw ValidationException::withMessages([
                'zip_file' => ['A ZIP import may contain at most 100 supported documents.'],
            ]);
        }

        return $count;
    }
}
