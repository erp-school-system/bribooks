<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\ChapterController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\PageController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\VersionController;
use App\Http\Controllers\API\WorkflowController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Authenticated
Route::middleware('auth:api')->group(function () {

    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/profile',  [AuthController::class, 'profile']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Books (authors manage their own; reviewers/admins can read any)
    Route::apiResource('books', BookController::class);

    // Versions
    Route::get ('books/{book}/versions',                   [VersionController::class, 'index']);
    Route::post('books/{book}/versions',                   [VersionController::class, 'store']);
    Route::get ('books/{book}/versions/{versionId}',       [VersionController::class, 'show']);
    Route::post('books/{book}/versions/{versionId}/rollback', [VersionController::class, 'rollback']);

    // Chapters
    Route::get ('books/{book}/chapters',   [ChapterController::class, 'index']);
    Route::post('books/{book}/chapters',   [ChapterController::class, 'store']);
    Route::put ('chapters/{chapter}',      [ChapterController::class, 'update']);
    Route::delete('chapters/{chapter}',   [ChapterController::class, 'destroy']);

    // Pages
    Route::get  ('chapters/{chapter}/pages', [PageController::class, 'index']);
    Route::post ('chapters/{chapter}/pages', [PageController::class, 'store']);
    Route::put  ('pages/{page}',             [PageController::class, 'update']);
    Route::delete('pages/{page}',            [PageController::class, 'destroy']);

    // Document upload
    Route::post('books/{book}/upload', [UploadController::class, 'upload']);

    // Workflow
    Route::post('books/{book}/submit',  [WorkflowController::class, 'submit']);
    Route::post('books/{book}/approve', [WorkflowController::class, 'approve']);
    Route::post('books/{book}/reject',  [WorkflowController::class, 'reject']);
    Route::post('books/{book}/publish', [WorkflowController::class, 'publish']);
});
