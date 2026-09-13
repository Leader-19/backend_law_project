<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML;
use Smalot\PdfParser\Parser;

class DocumentController extends Controller
{
    /**
     * Public preview: return up to $limit documents per category for guests.
     * This lets unauthenticated visitors see a sample of each category.
     */
    public function preview(Request $request)
    {
        $limit = min(max((int) $request->integer('limit', 5), 1), 10);

        $categories = Category::with([
            'documents' => fn ($q) => $q->latest()->limit($limit),
            'documents:id,doc_name,doc_title,description,doc_upload,image,category_id,created_at',
        ])
            ->withCount('documents')
            ->orderBy('title')
            ->get();

        $childrenMap = [];
        foreach ($categories as $category) {
            if ($category->parent_id) {
                $childrenMap[$category->parent_id][] = $category;
            }
        }

        $mapCategory = function ($category) use ($childrenMap, &$mapCategory) {
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
                ]),
                'subcategories' => isset($childrenMap[$category->id])
                    ? collect($childrenMap[$category->id])->map($mapCategory)->all()
                    : [],
            ];
        };

        $rootCategories = $categories->whereNull('parent_id');

        return response()->json([
            'status' => 'success',
            'categories' => $rootCategories->map($mapCategory)->all(),
            'is_preview' => true,
            'preview_limit' => $limit,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $allCategories = Category::with([
            // This is an API presentation limit: each category returns its
            // latest ten documents, while documents_count remains the total.
            'documents' => fn ($query) => $query->select('id', 'doc_name', 'doc_title', 'description', 'doc_upload', 'image', 'category_id', 'created_at')->latest()->limit(10),
        ])->withCount('documents')->orderBy('title')->get();

        if ($user && ! $user->hasRole('Admin')) {
            $viewableIds = $user->getViewableCategoryIds();
            $allCategories = $allCategories->whereIn('id', $viewableIds);
        }

        $categoriesById = $allCategories->keyBy('id');

        $childrenMap = [];
        foreach ($allCategories as $category) {
            if ($category->parent_id) {
                $childrenMap[$category->parent_id][] = $category;
            }
        }

        $mapCategory = function ($category) use ($childrenMap, &$mapCategory) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'documents_count' => $category->documents_count,
                'documents' => collect($category->documents)->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'doc_name' => $doc->doc_name,
                        'doc_title' => $doc->doc_title,
                        'description' => $doc->description,
                        'doc_upload' => $doc->doc_upload,
                        'image' => $doc->image,
                    ];
                }),
                'subcategories' => isset($childrenMap[$category->id])
                    ? collect($childrenMap[$category->id])->map($mapCategory)->all()
                    : [],
            ];
        };

        $rootCategories = $allCategories->whereNull('parent_id');

        return response()->json([
            'status' => 'success',
            'categories' => $rootCategories->map($mapCategory)->all(),
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
            $subService = app(\App\Services\SubscriptionService::class);
            $plan = $subService->currentPlanFor($user);
        }

        $isFreePlan = false;
        $limitPerCategory = null;
        $planName = $isAdmin ? 'Admin' : ($plan?->name ?? 'Free');

        if (! $isAdmin) {
            $isFreePlan = ! $plan || $plan->slug === 'free' || (float) ($plan->price ?? 0) === 0.0;
            // If Free plan or plan has a specific document limit per category
            $limitPerCategory = $isFreePlan ? ($plan->max_documents ?? 5) : ($plan->max_documents ?? null);
        }

        $viewableIds = $user->getViewableCategoryIds();

        $docLimit = $limitPerCategory ?? 100;

        $categories = Category::with([
            'documents' => fn ($query) => $query->select('id', 'doc_name', 'doc_title', 'description', 'doc_upload', 'image', 'category_id', 'created_at')
                ->latest()
                ->limit($docLimit)
        ])
            ->withCount('documents')
            ->whereIn('id', $viewableIds)
            ->orderBy('title')
            ->get();

        $childrenMap = [];
        foreach ($categories as $category) {
            if ($category->parent_id) {
                $childrenMap[$category->parent_id][] = $category;
            }
        }

        $mapCategory = function ($category) use ($childrenMap, &$mapCategory) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'documents_count' => $category->documents_count,
                'documents' => collect($category->documents)->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'doc_name' => $doc->doc_name,
                        'doc_title' => $doc->doc_title,
                        'description' => $doc->description,
                        'doc_upload' => $doc->doc_upload,
                        'image' => $doc->image,
                    ];
                }),
                'subcategories' => isset($childrenMap[$category->id])
                    ? collect($childrenMap[$category->id])->map($mapCategory)->all()
                    : [],
            ];
        };

        $rootCategories = $categories->whereNull('parent_id');

        return response()->json([
            'status' => 'success',
            'categories' => $rootCategories->map($mapCategory)->all(),
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

        $document = Document::with('category')->findOrFail($id);

        if (! $user->hasRole('Admin')) {
            $viewableCategoryIds = $user->getViewableCategoryIds();
            if (! in_array($document->category_id, $viewableCategoryIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'អ្នកមិនមានសិទ្ធិចូលមើលឯកសារនេះទេ (You do not have access to view this document).',
                ], 403);
            }
        }

        $path = storage_path('app/public/'.$document->doc_upload);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $content = '';
        try {
            if (file_exists($path)) {
                if ($ext === 'docx') {
                    $phpWord = IOFactory::load($path);
                    $writer = new HTML($phpWord);
                    $tempHtml = tempnam(sys_get_temp_dir(), 'docx').'.html';
                    $writer->save($tempHtml, 'HTML');
                    $content = file_get_contents($tempHtml);
                    unlink($tempHtml);
                } elseif ($ext === 'pdf') {
                    $parser = new Parser;
                    $pdf = $parser->parseFile($path);
                    $content = $pdf->getText();
                } elseif ($ext === 'xlsx') {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                    foreach ($spreadsheet->getAllSheets() as $sheet) {
                        foreach ($sheet->getRowIterator() as $row) {
                            $cells = [];
                            foreach ($row->getCellIterator() as $cell) {
                                $cells[] = $cell->getValue();
                            }
                            $content .= implode(' ', $cells)."\n";
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $content = 'Could not extract file content: '.$e->getMessage();
        }

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
        $document = Document::with('category')->findOrFail($id);

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
}
