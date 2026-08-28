<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && ! $user->hasRole('Admin')) {
            $viewableIds = $user->getViewableCategoryIds();

            $totalCategories = Category::whereIn('id', $viewableIds)->count();
            $totalDocuments = Document::whereIn('category_id', $viewableIds)->count();
        } else {
            $totalCategories = Category::count();
            $totalDocuments = Document::count();
        }

        $totalUsers = User::count();

        // Get all categories with document counts
        $categoriesQuery = Category::withCount('documents')
            ->orderBy('documents_count', 'desc');

        if ($user && ! $user->hasRole('Admin')) {
            $viewableIds = $user->getViewableCategoryIds();
            $categoriesQuery->whereIn('id', $viewableIds);
        }

        $allCategories = $categoriesQuery->get()
            ->map(fn ($cat) => [
                'name' => $cat->title,
                'count' => $cat->documents_count,
                'id' => $cat->id,
            ]);

        // Top 5 for the sidebar widget
        $topCategories = $allCategories->take(5);

        // Get recent documents
        $recentDocsQuery = Document::with('category')
            ->latest()
            ->limit(5);

        if ($user && ! $user->hasRole('Admin')) {
            $viewableIds = $user->getViewableCategoryIds();
            $recentDocsQuery->whereIn('category_id', $viewableIds);
        }

        $recentDocuments = $recentDocsQuery->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->doc_name,
                'category' => $doc->category?->title ?? 'Unknown',
                'created_at' => $doc->created_at->diffForHumans(),
            ]);

        return response()->json([
            'status' => 'success',
            'metrics' => [
                'total_users' => $totalUsers,
                'total_categories' => $totalCategories,
                'total_documents' => $totalDocuments,
            ],
            'top_categories' => $topCategories,
            'all_categories' => $allCategories,
            'recent_documents' => $recentDocuments,
        ]);
    }
}
