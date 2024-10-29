<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-user', [AuthController::class, 'verifyUser']);
Route::post('/login', [AuthController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::resource('/tags', TagController::class);
    Route::resource('/posts', PostController::class)->only('index', 'show', 'store', 'update', 'destroy');

    //trashed posts
    Route::get('/trashed/posts', [PostController::class, 'trashed'])->name('posts.trashed');
    //restore post
    Route::patch('/posts/{id}/restore', [PostController::class, 'restore'])->name('posts.restore');

    //stats
    Route::get('/stats', [PostController::class, 'stats']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

