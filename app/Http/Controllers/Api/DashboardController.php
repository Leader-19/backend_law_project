<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentLimitService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $subscription = null;
        $documentUsage = null;
        if ($user && ! $user->hasRole('Admin')) {
            $subscription = app(SubscriptionService::class)->currentSubscriptionFor($user);
            $documentUsage = app(DocumentLimitService::class)->usage($user);
        }

        if ($user && ! $user->hasRole('Admin')) {
            $viewableIds = $user->getViewableCategoryIds();

            $totalCategories = Category::whereIn('id', $viewableIds)->count();
            $totalDocuments = Document::whereIn('category_id', $viewableIds)->count();
        } else {
            $totalCategories = Category::count();
            $totalDocuments = Document::count();
        }

        // A normal user's dashboard must not disclose the size of the user base.
        $totalUsers = $user?->hasRole('Admin') ? User::count() : null;

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
            'subscription' => $subscription?->loadMissing('plan'),
            'document_usage' => $documentUsage,
        ]);
    }
}
