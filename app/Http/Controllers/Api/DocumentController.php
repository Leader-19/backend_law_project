<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $allCategories = Category::with('documents')->withCount('documents')->orderBy('title')->get();
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
                'documents' => $category->documents->map(function ($doc) {
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
        ]);
    }

    public function getContent(string $id)
    {
        $document = Document::with('category')->findOrFail($id);

        $path = storage_path('app/public/' . $document->doc_upload);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $content = '';
        try {
            if (file_exists($path)) {
                if ($ext === 'docx') {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                    $writer = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
                    $tempHtml = tempnam(sys_get_temp_dir(), 'docx') . '.html';
                    $writer->save($tempHtml, 'HTML');
                    $content = file_get_contents($tempHtml);
                    unlink($tempHtml);
                } elseif ($ext === 'pdf') {
                    $parser = new \Smalot\PdfParser\Parser();
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
                            $content .= implode(' ', $cells) . "\n";
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $content = 'Could not extract file content: ' . $e->getMessage();
        }

        return response()->json([
            'document' => [
                'id' => $document->id,
                'doc_name' => $document->doc_name,
                'doc_title' => $document->doc_title,
                'description' => $document->description,
                'category' => $document->category,
            ],
            'content' => $content
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
            ]
        ]);
    }
}
