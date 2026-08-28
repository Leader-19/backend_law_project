<?php

use App\Http\Controllers\Api\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\UserSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])->middleware('throttle:60,1');
Route::get('/subscription-plans/{id}', [SubscriptionPlanController::class, 'show'])->middleware('throttle:60,1');

// Public catalogue endpoints.
Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index'])->middleware('throttle:60,1');
Route::get('/documents', [DocumentController::class, 'index'])->middleware('throttle:60,1');
Route::get('/documents/preview', [DocumentController::class, 'preview'])->middleware('throttle:30,1');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->middleware('throttle:60,1');
Route::get('/documents/{id}/content', [DocumentController::class, 'getContent'])->middleware('throttle:30,1');
Route::get('/categories', [CategoryController::class, 'index'])->middleware('throttle:60,1');
Route::get('/categories/{id}', [CategoryController::class, 'show'])->middleware('throttle:60,1');

// Text Contents API
Route::get('/text-contents', [\App\Http\Controllers\Api\TextContentController::class, 'index'])->middleware('throttle:60,1');
Route::get('/text-contents/{id}', [\App\Http\Controllers\Api\TextContentController::class, 'show'])->middleware('throttle:60,1');
Route::post('/text-contents', [\App\Http\Controllers\Api\TextContentController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:30,1']);
Route::put('/text-contents/{id}', [\App\Http\Controllers\Api\TextContentController::class, 'update'])
    ->middleware(['auth:sanctum', 'throttle:30,1']);
Route::delete('/text-contents/{id}', [\App\Http\Controllers\Api\TextContentController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'throttle:10,1']);
Route::post('/categories', [CategoryController::class, 'store'])
    ->middleware(['auth:sanctum', 'permission:category.create', 'throttle:30,1', 'subscription.limit:category']);
Route::put('/categories/{id}', [CategoryController::class, 'update'])
    ->middleware(['auth:sanctum', 'permission:category.edit', 'throttle:30,1']);
Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'permission:category.delete', 'throttle:10,1']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:6,1');
    Route::get('/profile', [AuthController::class, 'profile'])->middleware('throttle:60,1');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('throttle:30,1');
    Route::post('/profile/avatar', [AuthController::class, 'updateAvatar'])->middleware('throttle:10,1');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->middleware('throttle:6,1');
    Route::get('/subscriptions', [UserSubscriptionController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/subscriptions', [UserSubscriptionController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/subscriptions/cancel', [UserSubscriptionController::class, 'cancel'])->middleware('throttle:10,1');

    Route::get('/my-categories', [CategoryController::class, 'myCategories'])->middleware('throttle:30,1');
    Route::get('/my-documents', [DocumentController::class, 'myDocuments'])->middleware('throttle:30,1');

    // Library API
    Route::get('/library', [\App\Http\Controllers\LibraryController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/library', [\App\Http\Controllers\LibraryController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/library/{documentId}', [\App\Http\Controllers\LibraryController::class, 'destroy'])->middleware('throttle:30,1');
    Route::delete('/library', [\App\Http\Controllers\LibraryController::class, 'bulkDestroy'])->middleware('throttle:10,1');
    Route::post('/library/check', [\App\Http\Controllers\LibraryController::class, 'check'])->middleware('throttle:30,1');

    // Reading History API
    Route::get('/reading-history', [\App\Http\Controllers\ReadingHistoryController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/reading-history/progress', [\App\Http\Controllers\ReadingHistoryController::class, 'updateProgress'])->middleware('throttle:30,1');

    // Quizzes API
    Route::get('/quizzes', [\App\Http\Controllers\QuizController::class, 'index'])->middleware('throttle:30,1');
    Route::get('/quizzes/{quiz}', [\App\Http\Controllers\QuizController::class, 'show'])->middleware('throttle:30,1');
    Route::post('/quizzes/{quiz}/submit', [\App\Http\Controllers\QuizController::class, 'submit'])->middleware('throttle:10,1');
    Route::get('/my-attempts', [\App\Http\Controllers\QuizController::class, 'myAttempts'])->middleware('throttle:30,1');

    // Leaderboard API
    Route::get('/leaderboard', [\App\Http\Controllers\LeaderboardController::class, 'index'])->middleware('throttle:30,1');

    // Certificates API
    Route::get('/certificates', [\App\Http\Controllers\CertificateController::class, 'index'])->middleware('throttle:30,1');
    Route::get('/certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show'])->middleware('throttle:30,1');
    Route::get('/certificates/{certificate}/download', [\App\Http\Controllers\CertificateController::class, 'download'])->middleware('throttle:10,1');

    // Contact Messages API
    Route::get('/contact', [\App\Http\Controllers\ContactController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])->middleware('throttle:10,1');

    // Chunked Upload API (large file uploads)
    Route::post('/upload/init', [\App\Http\Controllers\Api\ChunkedUploadController::class, 'init'])->middleware('throttle:30,1');
    Route::post('/upload/{uploadId}/chunk', [\App\Http\Controllers\Api\ChunkedUploadController::class, 'chunk'])->middleware('throttle:120,1');
    Route::post('/upload/{uploadId}/complete', [\App\Http\Controllers\Api\ChunkedUploadController::class, 'complete'])->middleware('throttle:10,1');
    Route::get('/upload/{uploadId}/status', [\App\Http\Controllers\Api\ChunkedUploadController::class, 'status'])->middleware('throttle:30,1');
    Route::delete('/upload/{uploadId}/cancel', [\App\Http\Controllers\Api\ChunkedUploadController::class, 'cancel'])->middleware('throttle:30,1');
});

// Admin user management API. Read and write actions intentionally use separate
// permissions so a user who can view accounts cannot change access or plans.
Route::middleware(['auth:sanctum', 'permission:users.view'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/plans', [AdminUserController::class, 'availablePlans'])->name('plans.index');
    Route::get('/categories', [AdminUserController::class, 'categories'])->name('categories.index');

    // Admin Subscription & Payment Management
    Route::get('/subscriptions', [AdminSubscriptionController::class, 'allSubscriptions'])->name('subscriptions.index');
    Route::get('/subscriptions/pending', [AdminSubscriptionController::class, 'pendingPayments'])->name('subscriptions.pending');
    Route::post('/subscriptions/{subscription}/approve', [AdminSubscriptionController::class, 'approvePayment'])->name('subscriptions.approve');
    Route::post('/subscriptions/{subscription}/reject', [AdminSubscriptionController::class, 'rejectPayment'])->name('subscriptions.reject');

    // Admin Plan Management
    Route::get('/all-plans', [AdminSubscriptionController::class, 'listPlans'])->name('plans.all');
    Route::get('/all-categories', [AdminSubscriptionController::class, 'allCategories'])->name('categories.all');
});

Route::middleware(['auth:sanctum', 'permission:users.create'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
});

Route::middleware(['auth:sanctum', 'permission:users.edit'])->prefix('admin')->name('admin.')->group(function () {
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/categories', [AdminUserController::class, 'assignCategories'])->name('users.categories.assign');
    Route::delete('/users/{user}/categories/{category}', [AdminUserController::class, 'removeCategory'])->name('users.categories.remove');
    Route::post('/users/{user}/plans', [AdminUserController::class, 'assignPlan'])->name('users.plans.assign');
    Route::post('/users/{user}/plans/cancel', [AdminUserController::class, 'cancelPlan'])->name('users.plans.cancel');

    // Admin Plan CRUD with Category Assignment
    Route::post('/plans', [AdminSubscriptionController::class, 'storePlan'])->name('plans.store');
    Route::put('/plans/{subscriptionPlan}', [AdminSubscriptionController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{subscriptionPlan}', [AdminSubscriptionController::class, 'destroyPlan'])->name('plans.destroy');
    Route::post('/plans/{subscriptionPlan}/categories', [AdminSubscriptionController::class, 'syncPlanCategories'])->name('plans.categories.sync');
});

Route::middleware(['auth:sanctum', 'permission:users.delete'])->prefix('admin')->name('admin.')->group(function () {
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});
