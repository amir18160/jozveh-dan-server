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



Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/account', [AccountController::class, 'getAccountDetails']);
    Route::put('/account', [AccountController::class, 'updateAccountDetails']);
    Route::post('/account/change-password', [AccountController::class, 'changePassword']);
    Route::post('/account/delete', [AccountController::class, 'deleteAccount']);
});


Route::middleware(['auth:sanctum', IsAdmin::class])->group(function () {
    Route::apiResource('users', UserController::class);
});


Route::get('/resources', [ResourceController::class, 'index']);
Route::get('/resources/{resource}', [ResourceController::class, 'show']);
Route::get('/resources/{id}/download', [ResourceController::class, 'download'])->name('resources.download.id');


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/resources', [ResourceController::class, 'store']);
    Route::put('/resources/{resource}', [ResourceController::class, 'update']);
    Route::patch('/resources/{resource}', [ResourceController::class, 'update']);
    Route::delete('/resources/{resource}', [ResourceController::class, 'destroy']);
    Route::get('/my-resources', [ResourceController::class, 'myResources']);
});


Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories-all-flat', [CategoryController::class, 'allFlat']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
});

Route::middleware(['auth:sanctum', IsAdmin::class])->group(function () {
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});


Route::get('/reviews', [ReviewController::class, 'index']);


Route::post('/reviews', [ReviewController::class, 'store'])->middleware('auth:sanctum');
Route::put('/reviews/{review}', [ReviewController::class, 'update'])->middleware('auth:sanctum');
Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->middleware('auth:sanctum');

//
Route::middleware(['auth:sanctum', IsAdmin::class])->group(function () {
    Route::get('/admin/reviews', [ReviewController::class, 'adminIndex']);
    Route::get('/admin/reviews/{review}', [ReviewController::class, 'show']);
    Route::delete('/admin/reviews/{review}', [ReviewController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-groups', [GroupController::class, 'myGroups']);
    Route::apiResource('groups', GroupController::class)->except(['index']);
});

Route::get('/groups', [GroupController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chat-messages', [ChatMessageController::class, 'index']);
    Route::apiResource('chat-messages', ChatMessageController::class)->except(['index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ai-search', [AiSearchController::class, 'search']);
});

Route::post('/reports', [ReportController::class, 'store'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', IsAdmin::class])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    Route::put('/reports/{report}', [ReportController::class, 'update']);
    Route::delete('/reports/{report}', [ReportController::class, 'destroy']);
});
