<?php

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ResourceController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\ChatMessageController;

use App\Http\Middleware\IsAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});


Route::apiResource('users', UserController::class)
    ->middleware(['auth:sanctum', IsAdmin::class]);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::apiResource('resources', ResourceController::class);
Route::get('resources/{id}/download', [ResourceController::class, 'download']);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('reviews', ReviewController::class);
Route::apiResource('reports', ReportController::class);
Route::apiResource('groups', GroupController::class);
Route::apiResource('chat-messages', ChatMessageController::class);
