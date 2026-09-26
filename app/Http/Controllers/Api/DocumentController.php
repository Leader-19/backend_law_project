<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML;
use Smalot\PdfParser\Parser;

class DocumentController extends Controller
{
    public function preview(Request $request)
    {
        $limit = min(max((int) $request->integer('limit', 5), 1), 10);

        $cacheKey = "api.documents.preview.{$limit}";

        $categories = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($limit) {
            $all = Category::with([
                'documents' => fn ($q) => $q
                    ->select('id', 'doc_name', 'doc_title', 'description', 'doc_upload', 'image', 'category_id', 'created_at')
                    ->latest()
                    ->limit($limit),
            ])
                ->withCount('documents')
                ->orderBy('title')
                ->get(['id', 'title', 'description', 'parent_id']);

            return $this->buildTreeWithDocuments($all);
        });

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
            'is_preview' => true,
            'preview_limit' => $limit,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('Admin');
        $viewableIds = $isAdmin ? null : ($user?->getViewableCategoryIds() ?? []);

        if (! $isAdmin && empty($viewableIds)) {
            return response()->json([
                'status' => 'success',
                'categories' => [],
                'documents_per_category_limit' => 10,
            ]);
        }

        $cacheKey = $isAdmin
            ? 'api.documents.index.admin'
            : 'api.documents.index.' . md5(implode(',', $viewableIds ?? []));

        $categories = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($isAdmin, $viewableIds) {
            $all = Category::with([
                'documents' => fn ($q) => $q
                    ->select('id', 'doc_name', 'doc_title', 'description', 'doc_upload', 'image', 'category_id', 'created_at')
                    ->latest()
                    ->limit(10),
            ])
                ->withCount('documents')
                ->when(! $isAdmin, fn ($q) => $q->whereIn('id', $viewableIds))
                ->orderBy('title')
                ->get(['id', 'title', 'description', 'parent_id']);

            return $this->buildTreeWithDocuments($all);
        });

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
            'documents_per_category_limit' => 10,
        ]);
    }

    public function myDocuments(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isAdmin = $user->hasRole('Admin');
        $activeSub = $user->activeSubscription()->with('plan')->first();
        $plan = $activeSub?->plan;

        if (! $plan && ! $isAdmin) {
            $plan = app(\App\Services\SubscriptionService::class)->currentPlanFor($user);
        }

        $isFreePlan = false;
        $limitPerCategory = null;
        $planName = $isAdmin ? 'Admin' : ($plan?->name ?? 'Free');

        if (! $isAdmin) {
            $isFreePlan = ! $plan || $plan->slug === 'free' || (float) ($plan->price ?? 0) === 0.0;
            $limitPerCategory = $isFreePlan ? ($plan->max_documents ?? 5) : ($plan->max_documents ?? null);
        }

        $viewableIds = $user->getViewableCategoryIds();
        $docLimit = $limitPerCategory ?? 100;

        if (empty($viewableIds) && ! $isAdmin) {
            return response()->json([
                'status' => 'success',
                'categories' => [],
                'is_free_plan' => $isFreePlan,
                'plan_name' => $planName,
                'documents_per_category_limit' => $limitPerCategory,
            ]);
        }

        $categories = Category::with([
            'documents' => fn ($q) => $q
                ->select('id', 'doc_name', 'doc_title', 'description', 'doc_upload', 'image', 'category_id', 'created_at')
                ->latest()
                ->limit($docLimit),
        ])
            ->withCount('documents')
            ->when(! $isAdmin, fn ($q) => $q->whereIn('id', $viewableIds))
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'parent_id']);

        $tree = $this->buildTreeWithDocuments($categories);

        return response()->json([
            'status' => 'success',
            'categories' => $tree,
            'is_free_plan' => $isFreePlan,
            'plan_name' => $planName,
            'documents_per_category_limit' => $limitPerCategory,
        ]);
    }

    public function getContent(Request $request, string $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'សូមចុះឈ្មោះ ឬចូលគណនីដើម្បីអានឯកសារនេះ (Please register or log in to view this document).',
                'requires_auth' => true,
            ], 401);
        }

        $document = Document::with('category:id,title')->findOrFail($id);

        if (! $user->hasRole('Admin')) {
            $viewableCategoryIds = $user->getViewableCategoryIds();
            if (! in_array($document->category_id, $viewableCategoryIds, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'អ្នកមិនមានសិទ្ធិចូលមើលឯកសារនេះទេ (You do not have access to view this document).',
                ], 403);
            }
        }

        // Cache extracted content (files don't change often)
        $cacheKey = "document.content.{$document->id}";

        $content = Cache::remember($cacheKey, now()->addHours(6), function () use ($document) {
            return $this->extractContent($document);
        });

        return response()->json([
            'document' => [
                'id' => $document->id,
                'doc_name' => $document->doc_name,
                'doc_title' => $document->doc_title,
                'description' => $document->description,
                'category' => $document->category,
            ],
            'content' => $content,
        ]);
    }

    public function show(string $id)
    {
        $document = Document::with('category:id,title')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'document' => [
                'id' => $document->id,
                'doc_name' => $document->doc_name,
                'doc_title' => $document->doc_title,
                'description' => $document->description,
                'doc_upload' => $document->doc_upload,
                'image' => $document->image,
                'category' => $document->category,
            ],
        ]);
    }

    private function buildTreeWithDocuments($categories, $parentId = null)
    {
        return $categories
            ->where('parent_id', $parentId)
            ->values()
            ->map(function ($category) use ($categories) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                    'documents_count' => $category->documents_count,
                    'documents' => $category->documents->map(fn ($doc) => [
                        'id' => $doc->id,
                        'doc_name' => $doc->doc_name,
                        'doc_title' => $doc->doc_title,
                        'description' => $doc->description,
                        'doc_upload' => $doc->doc_upload,
                        'image' => $doc->image,
                    ])->values(),
                    'subcategories' => $this->buildTreeWithDocuments($categories, $category->id),
                ];
            })
            ->all();
    }

    private function extractContent(Document $document): string
    {
        $path = storage_path('app/public/' . $document->doc_upload);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $content = '';

        try {
            if (! file_exists($path)) {
                return 'File not found.';
            }

            if ($ext === 'docx') {
                $phpWord = IOFactory::load($path);
                $writer = new HTML($phpWord);
                $tempHtml = tempnam(sys_get_temp_dir(), 'docx') . '.html';
                $writer->save($tempHtml, 'HTML');
                $content = file_get_contents($tempHtml);
                @unlink($tempHtml);
            } elseif ($ext === 'pdf') {
                $parser = new Parser;
                $pdf = $parser->parseFile($path);
                $content = $pdf->getText();
            } elseif (in_array($ext, ['xlsx', 'xls'], true)) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $cells = [];
                        foreach ($row->getCellIterator() as $cell) {
                            $cells[] = $cell->getValue();
                        }
                        $content .= implode(' ', $cells) . "\n";
                    }
                }
            }
        } catch (\Exception $e) {
            $content = 'Could not extract file content: ' . $e->getMessage();
        }

        return $content;
    }
}
