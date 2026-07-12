<?php

use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Public catalogue endpoints.
Route::get('/documents', [DocumentController::class, 'index']);
Route::get('/documents/{id}', [DocumentController::class, 'show']);
Route::get('/documents/{id}/content', [DocumentController::class, 'getContent']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::post('/categories', [CategoryController::class, 'store'])
    ->middleware(['auth:sanctum', 'permission:category.create']);
Route::put('/categories/{id}', [CategoryController::class, 'update'])
    ->middleware(['auth:sanctum', 'permission:category.edit']);
Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'permission:category.delete']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->middleware('throttle:6,1');
});
