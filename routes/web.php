<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryManagementController;
use App\Http\Controllers\CategoryUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FrontendUserController;
use App\Http\Controllers\TextContentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home')->middleware('throttle:60,1');

/**
 * Dashboard route
 */
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:dashboard.view', 'throttle:60,1'])
    ->name('dashboard');

Route::get('log-viewer', function () {
    return Inertia::render('LogViewer');
})->middleware(['auth', 'verified', 'throttle:30,1'])->name('log-viewer');

/**
 * Activity logs for create/update/delete actions.
 */
Route::get('activity-logs', [ActivityLogController::class, 'index'])
    ->middleware(['auth', 'verified', 'throttle:30,1'])
    ->name('activity-logs.index');
Route::delete('activity-logs/{activityLog}', [ActivityLogController::class, 'destroy'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])
    ->name('activity-logs.destroy');
Route::delete('activity-logs/bulk', [ActivityLogController::class, 'bulkDestroy'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])
    ->name('activity-logs.bulk-destroy');
Route::delete('activity-logs/clear', [ActivityLogController::class, 'clearAll'])
    ->middleware(['auth', 'verified', 'throttle:5,1'])
    ->name('activity-logs.clear');
Route::post('activity-logs/issue', [ActivityLogController::class, 'logIssue'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])
    ->name('activity-logs.issue');
Route::get('activity-logs/health-check', [ActivityLogController::class, 'checkDatabaseHealth'])
    ->middleware(['auth', 'verified', 'throttle:30,1'])
    ->name('activity-logs.health-check');

/**
 * Backup routes
 */
Route::get('backup', [BackupController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:backup.view', 'throttle:10,1'])
    ->name('backup.index');
Route::get('backup/download', [BackupController::class, 'download'])
    ->middleware(['auth', 'verified', 'permission:backup.download', 'throttle:5,1'])
    ->name('backup.download');

/**
 * User routes
 * create, retrieve, update, delete
 */
Route::resource('users', UserController::class)
    ->only(['create', 'store'])
    ->middleware(['auth', 'verified', 'permission:users.create', 'throttle:30,1']);

Route::resource('users', UserController::class)
    ->only(['edit', 'update'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1']);

Route::resource('users', UserController::class)
    ->only(['destroy'])
    ->middleware(['auth', 'verified', 'permission:users.delete', 'throttle:10,1']);

Route::resource('users', UserController::class)
    ->only(['index', 'show'])
    ->middleware(['auth', 'verified', 'permission:users.view', 'throttle:60,1']);

Route::get('frontend-users', [FrontendUserController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:users.view', 'throttle:60,1'])
    ->name('frontend-users.index');

Route::get('frontend-users/{id}', [FrontendUserController::class, 'show'])
    ->middleware(['auth', 'verified', 'permission:users.view', 'throttle:60,1'])
    ->name('frontend-users.show');

Route::get('frontend-users/{id}/edit', [FrontendUserController::class, 'edit'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1'])
    ->name('frontend-users.edit');

Route::put('frontend-users/{id}', [FrontendUserController::class, 'update'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1'])
    ->name('frontend-users.update');

Route::delete('frontend-users/{id}', [FrontendUserController::class, 'destroy'])
    ->middleware(['auth', 'verified', 'permission:users.delete', 'throttle:10,1'])
    ->name('frontend-users.destroy');

Route::get('frontend-users/{id}/categories', [FrontendUserController::class, 'categories'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1'])
    ->name('frontend-users.categories');

Route::post('frontend-users/{id}/categories', [FrontendUserController::class, 'storeCategories'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1'])
    ->name('frontend-users.categories.store');

Route::delete('frontend-users/{id}/categories/{category}', [FrontendUserController::class, 'removeCategory'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:10,1'])
    ->name('frontend-users.categories.destroy');

Route::post('frontend-users/{id}/plans', [FrontendUserController::class, 'assignPlan'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1'])
    ->name('frontend-users.plans.assign');

Route::post('frontend-users/{id}/plans/cancel', [FrontendUserController::class, 'cancelPlan'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1'])
    ->name('frontend-users.plans.cancel');

/**
 * Role routes
 */
Route::resource('roles', RoleController::class)
    ->only(['create', 'store'])
    ->middleware(['auth', 'verified', 'permission:roles.create', 'throttle:30,1']);

Route::resource('roles', RoleController::class)
    ->only(['edit', 'update'])
    ->middleware(['auth', 'verified', 'permission:roles.edit', 'throttle:30,1']);

Route::resource('roles', RoleController::class)
    ->only(['destroy'])
    ->middleware(['auth', 'verified', 'permission:roles.delete', 'throttle:10,1']);

Route::resource('roles', RoleController::class)
    ->only(['index', 'show'])
    ->middleware(['auth', 'verified', 'permission:roles.view', 'throttle:60,1']);

Route::get('permissions', [PermissionController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:roles.view', 'throttle:30,1'])
    ->name('permissions.index');
Route::post('permissions/scan', [PermissionController::class, 'scan'])
    ->middleware(['auth', 'verified', 'permission:roles.edit', 'throttle:10,1'])
    ->name('permissions.scan');
/**
 * End role route
 */

/**
 * Categories route
 */
Route::delete('categories/bulk', [CategoryController::class, 'bulkDestroy'])
    ->middleware(['auth', 'verified', 'permission:category.delete', 'throttle:10,1'])
    ->name('categories.bulk-destroy');
Route::resource('categories', CategoryController::class)
    ->only(['create', 'store'])
    ->middleware(['auth', 'verified', 'permission:category.create', 'throttle:30,1', 'subscription.limit:category']);
Route::resource('categories', CategoryController::class)
    ->only(['edit', 'update'])
    ->middleware(['auth', 'verified', 'permission:category.edit', 'throttle:30,1']);
Route::resource('categories', CategoryController::class)
    ->only(['destroy'])
    ->middleware(['auth', 'verified', 'permission:category.delete', 'throttle:10,1']);
Route::resource('categories', CategoryController::class)
    ->only(['index', 'show'])
    ->middleware(['auth', 'verified', 'permission:category.view', 'throttle:60,1']);

Route::get('categories/{category}/permissions', [CategoryUserController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:category.manage', 'throttle:30,1'])
    ->name('categories.permissions.index');
Route::post('categories/{category}/permissions', [CategoryUserController::class, 'store'])
    ->middleware(['auth', 'verified', 'permission:category.manage', 'throttle:30,1'])
    ->name('categories.permissions.store');
Route::put('categories/{category}/permissions/{user}', [CategoryUserController::class, 'update'])
    ->middleware(['auth', 'verified', 'permission:category.manage', 'throttle:30,1'])
    ->name('categories.permissions.update');
Route::delete('categories/{category}/permissions/{user}', [CategoryUserController::class, 'destroy'])
    ->middleware(['auth', 'verified', 'permission:category.manage', 'throttle:10,1'])
    ->name('categories.permissions.destroy');
Route::post('categories/{category}/team-permissions', [CategoryUserController::class, 'storeTeam'])
    ->middleware(['auth', 'verified', 'permission:category.manage', 'throttle:30,1'])
    ->name('categories.team-permissions.store');
Route::put('categories/{category}/team-permissions/{role}', [CategoryUserController::class, 'updateTeam'])
    ->middleware(['auth', 'verified', 'permission:category.manage', 'throttle:30,1'])
    ->name('categories.team-permissions.update');
Route::delete('categories/{category}/team-permissions/{role}', [CategoryUserController::class, 'destroyTeam'])
    ->middleware(['auth', 'verified', 'permission:category.manage', 'throttle:10,1'])
    ->name('categories.team-permissions.destroy');

/**
 * End Categories route
 */

/**
 * Category Management route
 */
Route::get('category-management', [CategoryManagementController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:document.view|document.create|document.edit|document.delete', 'throttle:60,1'])
    ->name('category-management.index');

/**
 * End Category Management route
 */

/**
 * Documents route
 */
Route::delete('documents/bulk', [DocumentController::class, 'bulkDestroy'])
    ->middleware(['auth', 'verified', 'permission:document.delete', 'throttle:10,1'])
    ->name('documents.bulk-destroy');
Route::get('documents/batch/create', [DocumentController::class, 'batchCreate'])
    ->middleware(['auth', 'verified', 'permission:document.create', 'throttle:30,1'])
    ->name('documents.batch.create');
Route::post('documents/batch', [DocumentController::class, 'batchStore'])
    ->middleware(['auth', 'verified', 'permission:document.create', 'throttle:10,1'])
    ->name('documents.batch.store');
Route::post('documents/batch/zip', [DocumentController::class, 'batchStoreZip'])
    ->middleware(['auth', 'verified', 'permission:document.create', 'throttle:5,1'])
    ->name('documents.batch.zip');
Route::resource('documents', DocumentController::class)
    ->only(['create', 'store'])
    ->middleware(['auth', 'verified', 'permission:document.create', 'throttle:30,1', 'subscription.limit:document']);
Route::resource('documents', DocumentController::class)
    ->only(['edit', 'update'])
    ->middleware(['auth', 'verified', 'permission:document.edit', 'throttle:30,1']);
Route::resource('documents', DocumentController::class)
    ->only(['destroy'])
    ->middleware(['auth', 'verified', 'permission:document.delete', 'throttle:10,1']);
Route::resource('documents', DocumentController::class)
    ->only(['index', 'show'])
    ->middleware(['auth', 'verified', 'permission:document.view', 'throttle:60,1']);

/**
 * End Documents route
 */

/**
 * Text Contents route
 */
Route::delete('text-contents/bulk', [TextContentController::class, 'bulkDestroy'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])
    ->name('text-contents.bulk-destroy');
Route::resource('text-contents', TextContentController::class)
    ->only(['create', 'store'])
    ->middleware(['auth', 'verified', 'throttle:30,1', 'subscription.limit:text_content']);
Route::resource('text-contents', TextContentController::class)
    ->only(['edit', 'update'])
    ->middleware(['auth', 'verified', 'throttle:30,1']);
Route::resource('text-contents', TextContentController::class)
    ->only(['destroy'])
    ->middleware(['auth', 'verified', 'throttle:10,1']);
Route::resource('text-contents', TextContentController::class)
    ->only(['index', 'show'])
    ->middleware(['auth', 'verified', 'throttle:60,1']);

/**
 * End Text Contents route
 */

Route::get('users/categories', [UserController::class, 'categories'])
    ->middleware(['auth', 'verified', 'permission:users.view', 'throttle:30,1'])
    ->name('users.categories.index');

Route::get('users/categories/assign', [UserController::class, 'categoryAssignments'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1'])
    ->name('users.categories.assign');

Route::post('users/categories/assign', [UserController::class, 'storeCategoryAssignments'])
    ->middleware(['auth', 'verified', 'permission:users.edit', 'throttle:30,1'])
    ->name('users.categories.assign.store');

/**
 * Category Dashboard
 */
Route::get('categories/{category}/dashboard', [CategoryController::class, 'dashboard'])
    ->middleware(['auth', 'verified', 'permission:category.view', 'throttle:60,1'])
    ->name('categories.dashboard');

use App\Http\Controllers\SubscriptionPlanWebController;

/**
 * Subscription Plans Backend UI routes
 */
Route::resource('subscription-plans', SubscriptionPlanWebController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified', 'permission:plans.view', 'throttle:60,1']);

Route::get('subscription-plans/{subscriptionPlan}/categories', [SubscriptionPlanWebController::class, 'categories'])
    ->middleware(['auth', 'verified', 'permission:plans.edit', 'throttle:30,1'])
    ->name('subscription-plans.categories');
Route::put('subscription-plans/{subscriptionPlan}/categories', [SubscriptionPlanWebController::class, 'syncCategories'])
    ->middleware(['auth', 'verified', 'permission:plans.edit', 'throttle:30,1'])
    ->name('subscription-plans.categories.update');

Route::get('payments', [PaymentController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:payments.view', 'throttle:60,1'])
    ->name('payments.index');
Route::post('payments/{subscription}/approve', [PaymentController::class, 'approve'])
    ->middleware(['auth', 'verified', 'permission:payments.approve', 'throttle:30,1'])
    ->name('payments.approve');
Route::post('payments/{subscription}/reject', [PaymentController::class, 'reject'])
    ->middleware(['auth', 'verified', 'permission:payments.reject', 'throttle:30,1'])
    ->name('payments.reject');

require __DIR__.'/settings.php';

Route::fallback(function () {
    return Inertia::render('NotFound');
})->middleware('web');
