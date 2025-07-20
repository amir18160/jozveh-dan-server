<?php

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ResourceController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\ChatMessageController;
use App\Http\Controllers\API\AccountController;
use App\Http\Controllers\API\AiSearchController;

use App\Http\Middleware\IsAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// authentication
Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});

// Authenticated account management routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/account', [AccountController::class, 'getAccountDetails']);
    Route::put('/account', [AccountController::class, 'updateAccountDetails']);
    Route::post('/account/change-password', [AccountController::class, 'changePassword']);
    Route::post('/account/delete', [AccountController::class, 'deleteAccount']);
});

// Authenticated user management routes
Route::middleware(['auth:sanctum', IsAdmin::class])->group(function () {
    Route::apiResource('users', UserController::class);
});

// resources
// Public resources routes
Route::get('/resources', [ResourceController::class, 'index']);
Route::get('/resources/{resource}', [ResourceController::class, 'show']);
Route::get('/resources/{id}/download', [ResourceController::class, 'download'])->name('resources.download.id');

// Authenticated resources routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/resources', [ResourceController::class, 'store']);
    Route::put('/resources/{resource}', [ResourceController::class, 'update']);
    Route::patch('/resources/{resource}', [ResourceController::class, 'update']);
    Route::delete('/resources/{resource}', [ResourceController::class, 'destroy']);
    Route::get('/my-resources', [ResourceController::class, 'myResources']);
});

// categories
// public categories routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories-all-flat', [CategoryController::class, 'allFlat']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categories/{category}', [CategoryController::class, 'store']);
});

Route::middleware(['auth:sanctum', IsAdmin::class])->group(function () {
    Route::put('/categories/{category}', [CategoryController::class, 'store']);
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});

// reviews
// Publicly get (approved by default) reviews for a resource. Admins can filter.
Route::get('/reviews', [ReviewController::class, 'index']);

// Authenticated user can create a review, or update THEIR OWN review's comment/rating
Route::post('/reviews', [ReviewController::class, 'store'])->middleware('auth:sanctum');
Route::put('/reviews/{review}', [ReviewController::class, 'update'])->middleware('auth:sanctum'); // User can update own, admin can update any + status
Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->middleware('auth:sanctum');

// Admin specific operations for reviews
Route::middleware(['auth:sanctum', IsAdmin::class])->group(function () { // Assuming 'is_admin' is your middleware alias
    Route::get('/admin/reviews', [ReviewController::class, 'adminIndex']); // New route for admin to see all reviews
    Route::get('/admin/reviews/{review}', [ReviewController::class, 'show']); // Admin can view any review detail by ID
    Route::delete('/admin/reviews/{review}', [ReviewController::class, 'destroy']); // Admin can delete any review
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-groups', [GroupController::class, 'myGroups']); // For user to see their owned groups
    Route::apiResource('groups', GroupController::class)->except(['index']); // Create, Update, Delete, Show are protected
});

Route::get('/groups', [GroupController::class, 'index']);

// Chat Message Routes
Route::middleware('auth:sanctum')->group(function () {
    // Listing messages requires being logged in to a group context
    Route::get('/chat-messages', [ChatMessageController::class, 'index']);
    // All other actions (store, update, destroy) are protected
    Route::apiResource('chat-messages', ChatMessageController::class)->except(['index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ai-search', [AiSearchController::class, 'search']);
});

Route::apiResource('reports', ReportController::class);
