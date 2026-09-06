<?php

use App\Http\Controllers\SubscriptionPlanWebController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateManagementController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryManagementController;
use App\Http\Controllers\CategoryUserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FrontendUserController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizManagementController;
use App\Http\Controllers\ReadingHistoryController;
use App\Http\Controllers\TextContentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserApprovalController;
use App\Http\Controllers\UserController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home')->middleware('throttle:60,1');

Route::get('verify/{certificateNumber}', [CertificateController::class, 'verify'])
    ->middleware('throttle:30,1')
    ->name('certificates.verify');

Route::get('contact', [ContactController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('contact.index');
Route::post('contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');
Route::get('contact/{contactMessage}', [ContactController::class, 'show'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('contact.show');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'route.security'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware(['permission:dashboard.view', 'throttle:60,1'])
        ->name('dashboard');

    Route::get('log-viewer', function () {
        return Inertia::render('LogViewer');
    })->middleware('throttle:30,1')->name('log-viewer');

    /*
    |--------------------------------------------------------------------------
    | Activity Logs
    |--------------------------------------------------------------------------
    */
    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->middleware(['throttle:30,1'])
        ->name('activity-logs.index');
    Route::delete('activity-logs/{activityLog}', [ActivityLogController::class, 'destroy'])
        ->middleware(['throttle:10,1'])
        ->name('activity-logs.destroy');
    Route::delete('activity-logs/bulk', [ActivityLogController::class, 'bulkDestroy'])
        ->middleware(['throttle:10,1'])
        ->name('activity-logs.bulk-destroy');
    Route::delete('activity-logs/clear', [ActivityLogController::class, 'clearAll'])
        ->middleware(['throttle:5,1'])
        ->name('activity-logs.clear');
    Route::post('activity-logs/issue', [ActivityLogController::class, 'logIssue'])
        ->middleware(['throttle:10,1'])
        ->name('activity-logs.issue');
    Route::get('activity-logs/health-check', [ActivityLogController::class, 'checkDatabaseHealth'])
        ->middleware(['throttle:30,1'])
        ->name('activity-logs.health-check');

    /*
    |--------------------------------------------------------------------------
    | Backup
    |--------------------------------------------------------------------------
    */
    Route::get('backup', [BackupController::class, 'index'])
        ->middleware(['permission:backup.view', 'throttle:10,1'])
        ->name('backup.index');
    Route::get('backup/download', [BackupController::class, 'download'])
        ->middleware(['permission:backup.download', 'throttle:5,1'])
        ->name('backup.download');

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:users.create')->prefix('users')->name('users.')->group(function () {
        Route::get('/create', [UserController::class, 'create'])->middleware('throttle:30,1')->name('create');
        Route::post('/', [UserController::class, 'store'])->middleware('throttle:30,1')->name('store');
    });

    Route::middleware('permission:users.edit')->prefix('users')->name('users.')->group(function () {
        Route::get('/{user}/edit', [UserController::class, 'edit'])->middleware('throttle:30,1')->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->middleware('throttle:30,1')->name('update');
    });

    Route::middleware('permission:users.delete')->prefix('users')->name('users.')->group(function () {
        Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy');
    });

    Route::middleware('permission:users.view')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('throttle:60,1')->name('index');
        Route::get('/{user}', [UserController::class, 'show'])->middleware('throttle:60,1')->name('show');
    });

    Route::get('users/categories', [UserController::class, 'categories'])
        ->middleware(['permission:users.view', 'throttle:30,1'])
        ->name('users.categories.index');

    Route::get('users/categories/assign', [UserController::class, 'categoryAssignments'])
        ->middleware(['permission:users.edit', 'throttle:30,1'])
        ->name('users.categories.assign');

    Route::post('users/categories/assign', [UserController::class, 'storeCategoryAssignments'])
        ->middleware(['permission:users.edit', 'throttle:30,1'])
        ->name('users.categories.assign.store');

    /*
    |--------------------------------------------------------------------------
    | Frontend Users
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:users.view')->prefix('frontend-users')->name('frontend-users.')->group(function () {
        Route::get('/', [FrontendUserController::class, 'index'])->middleware('throttle:60,1')->name('index');
        Route::get('/{id}', [FrontendUserController::class, 'show'])->middleware('throttle:60,1')->name('show');
    });

    Route::middleware('permission:users.edit')->prefix('frontend-users')->name('frontend-users.')->group(function () {
        Route::get('/{id}/edit', [FrontendUserController::class, 'edit'])->middleware('throttle:30,1')->name('edit');
        Route::put('/{id}', [FrontendUserController::class, 'update'])->middleware('throttle:30,1')->name('update');
        Route::delete('/{id}', [FrontendUserController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy');
        Route::get('/{id}/categories', [FrontendUserController::class, 'categories'])->middleware('throttle:30,1')->name('categories');
        Route::post('/{id}/categories', [FrontendUserController::class, 'storeCategories'])->middleware('throttle:30,1')->name('categories.store');
        Route::delete('/{id}/categories/{category}', [FrontendUserController::class, 'removeCategory'])->middleware('throttle:10,1')->name('categories.destroy');
        Route::post('/{id}/plans', [FrontendUserController::class, 'assignPlan'])->middleware('throttle:30,1')->name('plans.assign');
        Route::post('/{id}/plans/cancel', [FrontendUserController::class, 'cancelPlan'])->middleware('throttle:30,1')->name('plans.cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:roles.create')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/create', [RoleController::class, 'create'])->middleware('throttle:30,1')->name('create');
        Route::post('/', [RoleController::class, 'store'])->middleware('throttle:30,1')->name('store');
    });

    Route::middleware('permission:roles.edit')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->middleware('throttle:30,1')->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->middleware('throttle:30,1')->name('update');
    });

    Route::middleware('permission:roles.delete')->prefix('roles')->name('roles.')->group(function () {
        Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy');
    });

    Route::middleware('permission:roles.view')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('throttle:60,1')->name('index');
        Route::get('/{role}', [RoleController::class, 'show'])->middleware('throttle:60,1')->name('show');
    });

    Route::get('permissions', [PermissionController::class, 'index'])
        ->middleware(['permission:roles.view', 'throttle:30,1'])
        ->name('permissions.index');
    Route::post('permissions/scan', [PermissionController::class, 'scan'])
        ->middleware(['permission:roles.edit', 'throttle:10,1'])
        ->name('permissions.scan');

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:category.create')->prefix('categories')->name('categories.')->group(function () {
        Route::get('/create', [CategoryController::class, 'create'])->middleware(['throttle:30,1', 'subscription.limit:category'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->middleware(['throttle:30,1', 'subscription.limit:category'])->name('store');
    });

    Route::middleware('permission:category.edit')->prefix('categories')->name('categories.')->group(function () {
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->middleware('throttle:30,1')->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->middleware('throttle:30,1')->name('update');
    });

    Route::middleware('permission:category.delete')->prefix('categories')->name('categories.')->group(function () {
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy');
        Route::delete('/bulk', [CategoryController::class, 'bulkDestroy'])->middleware('throttle:10,1')->name('bulk-destroy');
    });

    Route::middleware('permission:category.view')->prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->middleware('throttle:60,1')->name('index');
        Route::get('/{category}', [CategoryController::class, 'show'])->middleware('throttle:60,1')->name('show');
    });

    Route::get('categories/{category}/permissions', [CategoryUserController::class, 'index'])
        ->middleware(['permission:category.manage', 'throttle:30,1'])
        ->name('categories.permissions.index');
    Route::post('categories/{category}/permissions', [CategoryUserController::class, 'store'])
        ->middleware(['permission:category.manage', 'throttle:30,1'])
        ->name('categories.permissions.store');
    Route::put('categories/{category}/permissions/{user}', [CategoryUserController::class, 'update'])
        ->middleware(['permission:category.manage', 'throttle:30,1'])
        ->name('categories.permissions.update');
    Route::delete('categories/{category}/permissions/{user}', [CategoryUserController::class, 'destroy'])
        ->middleware(['permission:category.manage', 'throttle:10,1'])
        ->name('categories.permissions.destroy');
    Route::post('categories/{category}/team-permissions', [CategoryUserController::class, 'storeTeam'])
        ->middleware(['permission:category.manage', 'throttle:30,1'])
        ->name('categories.team-permissions.store');
    Route::put('categories/{category}/team-permissions/{role}', [CategoryUserController::class, 'updateTeam'])
        ->middleware(['permission:category.manage', 'throttle:30,1'])
        ->name('categories.team-permissions.update');
    Route::delete('categories/{category}/team-permissions/{role}', [CategoryUserController::class, 'destroyTeam'])
        ->middleware(['permission:category.manage', 'throttle:10,1'])
        ->name('categories.team-permissions.destroy');

    Route::get('categories/{category}/dashboard', [CategoryController::class, 'dashboard'])
        ->middleware(['permission:category.view', 'throttle:60,1'])
        ->name('categories.dashboard');

    /*
    |--------------------------------------------------------------------------
    | Category Management
    |--------------------------------------------------------------------------
    */
    Route::get('category-management', [CategoryManagementController::class, 'index'])
        ->middleware(['permission:document.view|document.create|document.edit|document.delete', 'throttle:60,1'])
        ->name('category-management.index');

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:document.create')->prefix('documents')->name('documents.')->group(function () {
        Route::get('/batch/create', [DocumentController::class, 'batchCreate'])->middleware('throttle:30,1')->name('batch.create');
        Route::post('/batch', [DocumentController::class, 'batchStore'])->middleware('throttle:10,1')->name('batch.store');
        Route::post('/batch/zip', [DocumentController::class, 'batchStoreZip'])->middleware('throttle:5,1')->name('batch.zip');
        Route::get('/create', [DocumentController::class, 'create'])->middleware(['throttle:30,1', 'subscription.limit:document'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->middleware(['throttle:30,1', 'subscription.limit:document'])->name('store');
    });

    Route::middleware('permission:document.edit')->prefix('documents')->name('documents.')->group(function () {
        Route::get('/{document}/edit', [DocumentController::class, 'edit'])->middleware('throttle:30,1')->name('edit');
        Route::put('/{document}', [DocumentController::class, 'update'])->middleware('throttle:30,1')->name('update');
    });

    Route::middleware('permission:document.delete')->prefix('documents')->name('documents.')->group(function () {
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy');
        Route::delete('/bulk', [DocumentController::class, 'bulkDestroy'])->middleware('throttle:10,1')->name('bulk-destroy');
    });

    Route::middleware('permission:document.view')->prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->middleware('throttle:60,1')->name('index');
        Route::get('/{document}', [DocumentController::class, 'show'])->middleware('throttle:60,1')->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Text Contents
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:text_content.create')->prefix('text-contents')->name('text-contents.')->group(function () {
        Route::get('/create', [TextContentController::class, 'create'])->middleware(['throttle:30,1', 'subscription.limit:text_content'])->name('create');
        Route::post('/', [TextContentController::class, 'store'])->middleware(['throttle:30,1', 'subscription.limit:text_content'])->name('store');
    });

    Route::middleware('permission:text_content.edit')->prefix('text-contents')->name('text-contents.')->group(function () {
        Route::get('/{textContent}/edit', [TextContentController::class, 'edit'])->middleware('throttle:30,1')->name('edit');
        Route::put('/{textContent}', [TextContentController::class, 'update'])->middleware('throttle:30,1')->name('update');
    });

    Route::middleware('permission:text_content.delete')->prefix('text-contents')->name('text-contents.')->group(function () {
        Route::delete('/{textContent}', [TextContentController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy');
        Route::delete('/bulk', [TextContentController::class, 'bulkDestroy'])->middleware('throttle:10,1')->name('bulk-destroy');
    });

    Route::middleware('permission:text_content.view')->prefix('text-contents')->name('text-contents.')->group(function () {
        Route::get('/', [TextContentController::class, 'index'])->middleware('throttle:60,1')->name('index');
        Route::get('/{textContent}', [TextContentController::class, 'show'])->middleware('throttle:60,1')->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    */
    Route::resource('subscription-plans', SubscriptionPlanWebController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware(['permission:plans.view', 'throttle:60,1']);

    Route::get('subscription-plans/{subscriptionPlan}/categories', [SubscriptionPlanWebController::class, 'categories'])
        ->middleware(['permission:plans.edit', 'throttle:30,1'])
        ->name('subscription-plans.categories');
    Route::put('subscription-plans/{subscriptionPlan}/categories', [SubscriptionPlanWebController::class, 'syncCategories'])
        ->middleware(['permission:plans.edit', 'throttle:30,1'])
        ->name('subscription-plans.categories.update');

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */
    Route::get('payments', [PaymentController::class, 'index'])
        ->middleware(['permission:payments.view', 'throttle:60,1'])
        ->name('payments.index');
    Route::post('payments/{subscription}/approve', [PaymentController::class, 'approve'])
        ->middleware(['permission:payments.approve', 'throttle:30,1'])
        ->name('payments.approve');
    Route::post('payments/{subscription}/reject', [PaymentController::class, 'reject'])
        ->middleware(['permission:payments.reject', 'throttle:30,1'])
        ->name('payments.reject');

    /*
    |--------------------------------------------------------------------------
    | Library
    |--------------------------------------------------------------------------
    */
    Route::get('library', [LibraryController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('library.index');
    Route::post('library', [LibraryController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('library.store');
    Route::delete('library/{documentId}', [LibraryController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('library.destroy');
    Route::get('library/check', [LibraryController::class, 'check'])
        ->middleware('throttle:60,1')
        ->name('library.check');

    /*
    |--------------------------------------------------------------------------
    | Reading History
    |--------------------------------------------------------------------------
    */
    Route::get('reading-history', [ReadingHistoryController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('reading-history.index');
    Route::post('reading-history/progress', [ReadingHistoryController::class, 'updateProgress'])
        ->middleware('throttle:30,1')
        ->name('reading-history.update-progress');
    Route::get('reading-history/{documentId}/progress', [ReadingHistoryController::class, 'getProgress'])
        ->middleware('throttle:60,1')
        ->name('reading-history.get-progress');

    /*
    |--------------------------------------------------------------------------
    | Quizzes
    |--------------------------------------------------------------------------
    */
    Route::get('quizzes', [QuizController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('quizzes.index');
    Route::get('quizzes/{quiz}', [QuizController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('quizzes.show');
    Route::get('quizzes/{quiz}/take', [QuizController::class, 'take'])
        ->middleware('throttle:30,1')
        ->name('quizzes.take');
    Route::post('quizzes/{quiz}/submit', [QuizController::class, 'submit'])
        ->middleware('throttle:10,1')
        ->name('quizzes.submit');
    Route::get('quizzes/attempts/{attempt}', [QuizController::class, 'result'])
        ->middleware('throttle:60,1')
        ->name('quizzes.result');
    Route::get('my-attempts', [QuizController::class, 'myAttempts'])
        ->middleware('throttle:60,1')
        ->name('quizzes.my-attempts');

    /*
    |--------------------------------------------------------------------------
    | Leaderboard
    |--------------------------------------------------------------------------
    */
    Route::get('leaderboard', [LeaderboardController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('leaderboard.index');

    /*
    |--------------------------------------------------------------------------
    | Certificates
    |--------------------------------------------------------------------------
    */
    Route::get('certificates', [CertificateController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('certificates.index');
    Route::get('certificates/{certificate}', [CertificateController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('certificates.show');
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->middleware('throttle:10,1')
        ->name('certificates.download');

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Admin: User Approvals
    |--------------------------------------------------------------------------
    */
    Route::get('user-approvals', [UserApprovalController::class, 'index'])
        ->middleware(['permission:users.view', 'throttle:60,1'])
        ->name('user-approvals.index');
    Route::post('user-approvals/{user}/approve', [UserApprovalController::class, 'approve'])
        ->middleware(['permission:users.edit', 'throttle:30,1'])
        ->name('user-approvals.approve');
    Route::post('user-approvals/{user}/reject', [UserApprovalController::class, 'reject'])
        ->middleware(['permission:users.edit', 'throttle:30,1'])
        ->name('user-approvals.reject');
    Route::post('user-approvals/{user}/deactivate', [UserApprovalController::class, 'deactivate'])
        ->middleware(['permission:users.edit', 'throttle:30,1'])
        ->name('user-approvals.deactivate');
    Route::post('user-approvals/{user}/activate', [UserApprovalController::class, 'activate'])
        ->middleware(['permission:users.edit', 'throttle:30,1'])
        ->name('user-approvals.activate');
    Route::post('user-approvals/bulk-approve', [UserApprovalController::class, 'bulkApprove'])
        ->middleware(['permission:users.edit', 'throttle:10,1'])
        ->name('user-approvals.bulk-approve');
    Route::post('user-approvals/bulk-reject', [UserApprovalController::class, 'bulkReject'])
        ->middleware(['permission:users.edit', 'throttle:10,1'])
        ->name('user-approvals.bulk-reject');

    /*
    |--------------------------------------------------------------------------
    | Admin: Quiz Management
    |--------------------------------------------------------------------------
    */
    Route::get('quiz-management', [QuizManagementController::class, 'index'])
        ->middleware(['permission:document.view|document.create', 'throttle:60,1'])
        ->name('quizzes-management.index');
    Route::get('quiz-management/create', [QuizManagementController::class, 'create'])
        ->middleware(['permission:document.create', 'throttle:30,1'])
        ->name('quizzes-management.create');
    Route::post('quiz-management', [QuizManagementController::class, 'store'])
        ->middleware(['permission:document.create', 'throttle:10,1'])
        ->name('quizzes-management.store');
    Route::get('quiz-management/{quiz}/edit', [QuizManagementController::class, 'edit'])
        ->middleware(['permission:document.edit', 'throttle:30,1'])
        ->name('quizzes-management.edit');
    Route::put('quiz-management/{quiz}', [QuizManagementController::class, 'update'])
        ->middleware(['permission:document.edit', 'throttle:10,1'])
        ->name('quizzes-management.update');
    Route::delete('quiz-management/{quiz}', [QuizManagementController::class, 'destroy'])
        ->middleware(['permission:document.delete', 'throttle:10,1'])
        ->name('quizzes-management.destroy');
    Route::post('quiz-management/{quiz}/questions', [QuizManagementController::class, 'storeQuestion'])
        ->middleware(['permission:document.create', 'throttle:10,1'])
        ->name('quizzes-management.questions.store');
    Route::put('quiz-management/{quiz}/questions/{question}', [QuizManagementController::class, 'updateQuestion'])
        ->middleware(['permission:document.edit', 'throttle:10,1'])
        ->name('quizzes-management.questions.update');
    Route::delete('quiz-management/{quiz}/questions/{question}', [QuizManagementController::class, 'destroyQuestion'])
        ->middleware(['permission:document.delete', 'throttle:10,1'])
        ->name('quizzes-management.questions.destroy');
    Route::post('quiz-management/{quiz}/reorder', [QuizManagementController::class, 'reorderQuestions'])
        ->middleware(['permission:document.edit', 'throttle:10,1'])
        ->name('quizzes-management.questions.reorder');
    Route::get('quiz-management/{quiz}/attempts', [QuizManagementController::class, 'attempts'])
        ->middleware(['permission:document.view', 'throttle:60,1'])
        ->name('quizzes-management.attempts');

    /*
    |--------------------------------------------------------------------------
    | Admin: Certificate Management
    |--------------------------------------------------------------------------
    */
    Route::get('certificate-management', [CertificateManagementController::class, 'index'])
        ->middleware(['permission:document.view', 'throttle:60,1'])
        ->name('certificates-management.index');
    Route::get('certificate-management/{certificate}', [CertificateManagementController::class, 'show'])
        ->middleware(['permission:document.view', 'throttle:60,1'])
        ->name('certificates-management.show');
    Route::post('certificate-management/{certificate}/regenerate', [CertificateManagementController::class, 'regenerate'])
        ->middleware(['permission:document.edit', 'throttle:10,1'])
        ->name('certificates-management.regenerate');
    Route::delete('certificate-management/{certificate}', [CertificateManagementController::class, 'destroy'])
        ->middleware(['permission:document.delete', 'throttle:10,1'])
        ->name('certificates-management.destroy');

    /*
    |--------------------------------------------------------------------------
    | Admin: Contact Messages
    |--------------------------------------------------------------------------
    */
    Route::get('contact-messages', [ContactMessageController::class, 'index'])
        ->middleware(['permission:users.view', 'throttle:60,1'])
        ->name('contact-messages.index');
    Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])
        ->middleware(['permission:users.view', 'throttle:60,1'])
        ->name('contact-messages.show');
    Route::post('contact-messages/{contactMessage}/reply', [ContactMessageController::class, 'reply'])
        ->middleware(['permission:users.edit', 'throttle:10,1'])
        ->name('contact-messages.reply');
    Route::post('contact-messages/{contactMessage}/close', [ContactMessageController::class, 'close'])
        ->middleware(['permission:users.edit', 'throttle:10,1'])
        ->name('contact-messages.close');
    Route::post('contact-messages/{contactMessage}/reopen', [ContactMessageController::class, 'reopen'])
        ->middleware(['permission:users.edit', 'throttle:10,1'])
        ->name('contact-messages.reopen');
    Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])
        ->middleware(['permission:users.delete', 'throttle:10,1'])
        ->name('contact-messages.destroy');
});

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/settings.php';

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return Inertia::render('NotFound');
})->middleware('web');
