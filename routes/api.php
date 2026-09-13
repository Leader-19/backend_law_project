<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChunkedUploadController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\QuizController;
// use App\Http\Controllers\Api\ReadingHistoryController; // Not needed - using full namespace below
use App\Http\Controllers\Api\ReceiptPaymentController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\TextContentController;
use App\Http\Controllers\Api\UserSubscriptionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TelegramPaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/
Route::get('/health', HealthCheckController::class);
Route::post('/webhooks/stripe', StripeWebhookController::class)->middleware('throttle:120,1');
Route::post('/webhooks/telegram/payments', TelegramPaymentWebhookController::class)->middleware('throttle:120,1');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])->middleware('throttle:60,1');
Route::get('/subscription-plans/{id}', [SubscriptionPlanController::class, 'show'])->middleware('throttle:60,1');

Route::get('/documents', [DocumentController::class, 'index'])->middleware('throttle:60,1');
Route::get('/documents/preview', [DocumentController::class, 'preview'])->middleware('throttle:30,1');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->middleware('throttle:60,1');
Route::get('/documents/{id}/content', [DocumentController::class, 'getContent'])->middleware('throttle:30,1');
Route::get('/categories', [CategoryController::class, 'index'])->middleware('throttle:60,1');
Route::get('/categories/{id}', [CategoryController::class, 'show'])->middleware('throttle:60,1');
Route::get('/text-contents', [TextContentController::class, 'index'])->middleware('throttle:60,1');
Route::get('/text-contents/{id}', [TextContentController::class, 'show'])->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| Authenticated API Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'route.security'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('throttle:60,1');

    // Auth
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:6,1');
    Route::get('/profile', [AuthController::class, 'profile'])->middleware('throttle:60,1');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('throttle:30,1');
    Route::post('/profile/avatar', [AuthController::class, 'updateAvatar'])->middleware('throttle:10,1');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->middleware('throttle:6,1');

    // Subscriptions
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->middleware('throttle:10,1');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->middleware('throttle:10,1');
    Route::get('/subscriptions', [UserSubscriptionController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/subscriptions', [UserSubscriptionController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/subscriptions/cancel', [UserSubscriptionController::class, 'cancel'])->middleware('throttle:10,1');
    Route::post('/payments/receipt', [ReceiptPaymentController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/payments/history', [ReceiptPaymentController::class, 'history'])->middleware('throttle:30,1');
    Route::get('/subscriptions/current', [ReceiptPaymentController::class, 'current'])->middleware('throttle:30,1');

    // User Categories & Documents
    Route::get('/my-categories', [CategoryController::class, 'myCategories'])->middleware('throttle:30,1');
    Route::get('/my-documents', [DocumentController::class, 'myDocuments'])->middleware('throttle:30,1');
    Route::get('/documents/{id}/content', [DocumentController::class, 'getContent'])->middleware('throttle:30,1');

    // Text Contents
    Route::post('/text-contents', [TextContentController::class, 'store'])->middleware(['throttle:30,1', 'subscription.limit:text_content']);
    Route::put('/text-contents/{id}', [TextContentController::class, 'update'])->middleware(['throttle:30,1', 'subscription.limit:text_content']);
    Route::delete('/text-contents/{id}', [TextContentController::class, 'destroy'])->middleware('throttle:10,1');

    // Categories
    Route::post('/categories', [CategoryController::class, 'store'])->middleware(['permission:category.create', 'throttle:30,1', 'subscription.limit:category']);
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->middleware(['permission:category.edit', 'throttle:30,1']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware(['permission:category.delete', 'throttle:10,1']);

    // Library
    Route::get('/library', [\App\Http\Controllers\LibraryController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/library', [\App\Http\Controllers\LibraryController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/library/{documentId}', [\App\Http\Controllers\LibraryController::class, 'destroy'])->middleware('throttle:30,1');
    Route::delete('/library', [\App\Http\Controllers\LibraryController::class, 'bulkDestroy'])->middleware('throttle:10,1');
    Route::post('/library/check', [\App\Http\Controllers\LibraryController::class, 'check'])->middleware('throttle:30,1');

    // Reading History
    Route::get('/reading-history', [\App\Http\Controllers\Api\ReadingHistoryController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/reading-history/progress', [\App\Http\Controllers\Api\ReadingHistoryController::class, 'updateProgress'])->middleware('throttle:30,1');
    Route::get('/reading-history/{documentId}/progress', [\App\Http\Controllers\Api\ReadingHistoryController::class, 'getProgress'])->middleware('throttle:30,1');

    // Quizzes
    Route::get('/quizzes', [\App\Http\Controllers\Api\QuizController::class, 'index'])->middleware('throttle:30,1');
    Route::get('/quizzes/{quiz}', [\App\Http\Controllers\Api\QuizController::class, 'show'])->middleware('throttle:30,1');
    Route::get('/quizzes/{quiz}/take', [\App\Http\Controllers\Api\QuizController::class, 'take'])->middleware('throttle:30,1');
    Route::post('/quizzes/{quiz}/submit', [\App\Http\Controllers\Api\QuizController::class, 'submit'])->middleware('throttle:10,1');
    Route::get('/quizzes/attempts/{attempt}', [\App\Http\Controllers\Api\QuizController::class, 'result'])->middleware('throttle:30,1');
    Route::get('/my-attempts', [\App\Http\Controllers\Api\QuizController::class, 'myAttempts'])->middleware('throttle:30,1');

    // Leaderboard
    Route::get('/leaderboard', [\App\Http\Controllers\Api\LeaderboardController::class, 'index'])->middleware('throttle:30,1');

    // Certificates
    Route::get('/certificates', [\App\Http\Controllers\Api\CertificateController::class, 'index'])->middleware('throttle:30,1');
    Route::get('/certificates/{certificate}', [\App\Http\Controllers\Api\CertificateController::class, 'show'])->middleware('throttle:30,1');
    Route::get('/certificates/{certificate}/download', [\App\Http\Controllers\Api\CertificateController::class, 'download'])->middleware('throttle:10,1');

    // Contact Messages
    Route::get('/contact', [\App\Http\Controllers\ContactController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])->middleware('throttle:10,1');

    // Chunked Upload API
    Route::post('/upload/init', [ChunkedUploadController::class, 'init'])->middleware('throttle:30,1');
    Route::post('/upload/{uploadId}/chunk', [ChunkedUploadController::class, 'chunk'])->middleware('throttle:120,1');
    Route::post('/upload/{uploadId}/complete', [ChunkedUploadController::class, 'complete'])->middleware('throttle:10,1');
    Route::get('/upload/{uploadId}/status', [ChunkedUploadController::class, 'status'])->middleware('throttle:30,1');
    Route::delete('/upload/{uploadId}/cancel', [ChunkedUploadController::class, 'cancel'])->middleware('throttle:30,1');
});
