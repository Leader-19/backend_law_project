<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryManagementController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

/**
  * Dashboard route
  */
Route::get('dashboard', function () {
    return Inertia::render('Dashboard', [
        'categories' => \App\Models\Category::withCount('documents')->get(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('log-viewer', function () {
    return Inertia::render('LogViewer');
})->middleware(['auth', 'verified'])->name('log-viewer');

/**
 * Backup route
 */
Route::get('backup/download', [BackupController::class, 'download'])
    ->middleware('auth')
    ->name('backup.download');

/**
 * User routes
 * create, retrieve, update, delete
 */
Route::resource('users', UserController::class)
    ->only(['create', 'store'])
    ->middleware('permission:users.create');

Route::resource('users', UserController::class)
    ->only(['edit', 'update'])
    ->middleware('permission:users.edit');

Route::resource('users', UserController::class)
    ->only(['destroy'])
    ->middleware('permission:users.delete');

Route::resource('users', UserController::class)
    ->only(['index', 'show'])
    ->middleware('permission:users.create|users.edit|users.delete|users.view');

/**
 * Role routes
 */
Route::resource('roles', RoleController::class)
    ->only(['create', 'store'])
    ->middleware('permission:roles.create');

Route::resource('roles', RoleController::class)
    ->only(['edit', 'update'])
    ->middleware('permission:roles.edit');

Route::resource('roles', RoleController::class)
    ->only(['destroy'])
    ->middleware('permission:roles.delete');

Route::resource('roles', RoleController::class)
    ->only(['index', 'show'])
    ->middleware('permission:roles.create|roles.edit|roles.delete|roles.view');

Route::get('permissions', [PermissionController::class, 'index'])
    ->middleware('permission:roles.view|roles.edit')
    ->name('permissions.index');
Route::post('permissions/scan', [PermissionController::class, 'scan'])
    ->middleware('permission:roles.edit')
    ->name('permissions.scan');
/**
 * End role route
 */

/**
 * Categories route
 */
Route::delete('categories/bulk', [CategoryController::class, 'bulkDestroy'])
    ->middleware('permission:category.delete')
    ->name('categories.bulk-destroy');
Route::resource('categories', CategoryController::class)
    ->only(['create', 'store'])
    ->middleware('permission:category.create');
Route::resource('categories', CategoryController::class)
    ->only(['edit', 'update'])
    ->middleware('permission:category.edit');
Route::resource('categories', CategoryController::class)
    ->only(['destroy'])
    ->middleware('permission:category.delete');
Route::resource('categories', CategoryController::class)
    ->only(['index', 'show'])
    ->middleware('permission:category.create|category.edit|category.delete|category.view');

/**
  * End Categories route
  */

/**
  * Category Management route
  */
Route::get('category-management', [CategoryManagementController::class, 'index'])
    ->middleware(['auth', 'permission:document.view|document.create|document.edit|document.delete'])
    ->name('category-management.index');

/**
  * End Category Management route
  */

/**
 * Documents route
 */
Route::delete('documents/bulk', [DocumentController::class, 'bulkDestroy'])
    ->middleware('auth')
    ->name('documents.bulk-destroy');
Route::resource('documents', DocumentController::class)
    ->only(['create', 'store'])
    ->middleware('auth');
Route::resource('documents', DocumentController::class)
    ->only(['edit', 'update'])
    ->middleware('auth');
Route::resource('documents', DocumentController::class)
    ->only(['destroy'])
    ->middleware('auth');
Route::resource('documents', DocumentController::class)
    ->only(['index', 'show'])
    ->middleware('auth');

/**
 * End Documents route
 */





require __DIR__ . '/settings.php';
