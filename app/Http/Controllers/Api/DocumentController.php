<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Display all categories with their documents
     */
    public function index(Request $request)
    {
        $categories = Category::with(['documents' => function ($query) {
            $query->select('id', 'doc_name', 'doc_title', 'description', 'doc_upload', 'image', 'category_id', 'created_at');
        }])->withCount('documents')->get();

        return response()->json([
            'status' => 'success',
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'description' => $category->description,
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
                ];
            }),
        ]);
    }

    /**
     * Get document content for viewing
     */
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

    /**
     * Display the specified resource.
     */
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
