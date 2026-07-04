<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/users/profile/{username?}', [AuthController::class, 'profile']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth profile
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/update', [AuthController::class, 'update']);
    Route::put('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/users/{username}/follow', [AuthController::class, 'toggleFollow']);
    Route::get('/users/suggestions', [AuthController::class, 'suggestions']);
    Route::get('/trends', [PostController::class, 'trends']);

    // Posts & Feed
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::post('/posts/{id}/like', [PostController::class, 'like']);
    Route::post('/posts/{id}/bookmark', [PostController::class, 'bookmark']);
    Route::post('/posts/{id}/repost', [PostController::class, 'repost']);

    // Bookmarks list
    Route::get('/bookmarks', [PostController::class, 'bookmarks']);

    // Comments & Replies
    Route::post('/comments', [CommentController::class, 'store']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::put('/notifications/mark-read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications', [NotificationController::class, 'clearAll']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});
