<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display all categories with their documents
     */
    public function index()
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
     * Display the specified category with documents
     */
    public function show(string $id)
    {
        $category = Category::with(['documents' => function ($query) {
            $query->select('id', 'doc_name', 'doc_title', 'description', 'doc_upload', 'image', 'category_id', 'created_at');
        }])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'category' => [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
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
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
