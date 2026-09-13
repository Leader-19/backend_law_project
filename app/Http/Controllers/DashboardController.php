<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TextContent;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\DocumentLimitService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Admin')) app(SubscriptionService::class)->ensureFreeSubscription($user);
        $activitySearch = trim((string) $request->query('activity_search', ''));
        $activityLimit = min(max((int) $request->integer('activity_limit', 5), 1), 20);

        if ($user->hasRole('Admin')) {
            $stats = [
                'total_categories' => Category::count(),
                'total_users' => User::count(),
                'total_documents' => Document::count(),
                'total_text_contents' => TextContent::count(),
                'total_activities' => ActivityLog::count(),
                'pending_approvals' => User::where('status', 'pending')->count(),
                'total_quizzes' => Quiz::count(),
                'total_quiz_attempts' => QuizAttempt::count(),
                'total_certificates' => Certificate::count(),
                'open_messages' => ContactMessage::where('status', 'open')->count(),
            ];

            $activityQuery = ActivityLog::with('causer');

            if ($activitySearch !== '') {
                $activityQuery->where(function ($q) use ($activitySearch) {
                    $q->where('description', 'like', "%{$activitySearch}%")
                        ->orWhere('action', 'like', "%{$activitySearch}%")
                        ->orWhereHas('causer', function ($cq) use ($activitySearch) {
                            $cq->where('name', 'like', "%{$activitySearch}%");
                        });
                });
            }

            $recentActivity = $activityQuery
                ->latest()
                ->limit($activityLimit)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'severity' => $log->severity,
                    'description' => $log->description,
                    'causer_name' => $log->causer?->name ?? 'Automated task',
                    'created_at' => $log->created_at?->diffForHumans(),
                ])
                ->all();

            $recentDocuments = Document::with('category', 'user')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($doc) => [
                    'id' => $doc->id,
                    'doc_name' => $doc->doc_name,
                    'category_title' => $doc->category?->title,
                    'user_name' => $doc->user?->name,
                    'created_at' => $doc->created_at?->diffForHumans(),
                ])
                ->all();

            // Categories with document counts for admin overview
            $categories = Category::withCount('documents')
                ->orderBy('title')
                ->get()
                ->map(fn ($cat) => [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'description' => $cat->description,
                    'documents_count' => $cat->documents_count ?? 0,
                    'parent_id' => $cat->parent_id,
                ])
                ->all();
        } else {
            $viewableIds = $user->getViewableCategoryIds();

            $viewableQuizIds = Quiz::whereIn('category_id', $viewableIds)->pluck('id');

            $stats = [
                'total_categories' => Category::whereIn('id', $viewableIds)->count(),
                'total_users' => User::count(),
                'total_documents' => Document::whereIn('category_id', $viewableIds)->count(),
                'total_text_contents' => TextContent::whereIn('category_id', $viewableIds)->count(),
                'total_activities' => ActivityLog::whereHas('subject', function ($query) use ($viewableIds) {
                    $query->whereIn('id', $viewableIds);
                })->count(),
                'pending_approvals' => User::where('status', 'pending')->count(),
                'total_quizzes' => Quiz::whereIn('category_id', $viewableIds)->count(),
                'total_quiz_attempts' => QuizAttempt::whereIn('quiz_id', $viewableQuizIds)->count(),
                'total_certificates' => Certificate::whereIn('quiz_id', $viewableQuizIds)->count(),
                'open_messages' => ContactMessage::where('status', 'open')->count(),
            ];

            $activityQuery = ActivityLog::whereHas('subject', function ($query) use ($viewableIds) {
                $query->whereIn('id', $viewableIds);
            })->with('causer');

            if ($activitySearch !== '') {
                $activityQuery->where(function ($q) use ($activitySearch) {
                    $q->where('description', 'like', "%{$activitySearch}%")
                        ->orWhere('action', 'like', "%{$activitySearch}%")
                        ->orWhereHas('causer', function ($cq) use ($activitySearch) {
                            $cq->where('name', 'like', "%{$activitySearch}%");
                        });
                });
            }

            $recentActivity = $activityQuery
                ->latest()
                ->limit($activityLimit)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'severity' => $log->severity,
                    'description' => $log->description,
                    'causer_name' => $log->causer?->name ?? 'Automated task',
                    'created_at' => $log->created_at?->diffForHumans(),
                ])
                ->all();

            $recentDocuments = Document::whereIn('category_id', $viewableIds)
                ->with('category', 'user')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($doc) => [
                    'id' => $doc->id,
                    'doc_name' => $doc->doc_name,
                    'category_title' => $doc->category?->title,
                    'user_name' => $doc->user?->name,
                    'created_at' => $doc->created_at?->diffForHumans(),
                ])
                ->all();

            // Only show categories the user has direct permissions on
            $categories = Category::whereIn('id', $viewableIds)
                ->withCount('documents')
                ->get()
                ->map(function ($cat) use ($user) {
                    return [
                        'id' => $cat->id,
                        'title' => $cat->title,
                        'description' => $cat->description,
                        'documents_count' => $cat->documents_count ?? 0,
                        'permission' => $user->categoryPermissions()
                            ->where('category_id', $cat->id)
                            ->first()?->pivot->permission ?? 'view',
                    ];
                })
                ->all();
        }

        $subscription = $user->hasRole('Admin') ? null : UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest()
            ->first();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'recent_documents' => $recentDocuments,
            'categories' => $categories ?? [],
            'activity_search' => $activitySearch,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at?->format('Y-m-d'),
                'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                'plan' => $subscription->plan ? [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                    'price' => $subscription->plan->price,
                    'currency' => $subscription->plan->currency,
                    'max_categories' => $subscription->plan->max_categories,
                    'max_documents' => $subscription->plan->max_documents,
                    'max_text_contents' => $subscription->plan->max_text_contents,
                    'max_storage_mb' => $subscription->plan->max_storage_mb,
                ] : null,
            ] : null,
            'document_usage' => $user->hasRole('Admin') ? null : app(DocumentLimitService::class)->usage($user),
        ]);
    }
}
