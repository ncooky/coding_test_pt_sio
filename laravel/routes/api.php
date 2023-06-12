<?php

use Illuminate\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PassportAuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Models\PostComments;
use App\Models\PostImages;
use App\Models\PostLikes;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('register', [PassportAuthController::class, 'register']);
Route::post('login', [PassportAuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    // Route Post
    Route::get('posts',  [PostController::class, 'index'])->name('post.list');
    Route::get('posts/{id}',  [PostController::class, 'show'])->name('post.get');
    Route::get('posts/ping', [PostController::class, 'ping']);
    Route::post('posts/new',  [PostController::class, 'store'])->name('post.new');
    Route::post('posts/like/{id}',  [PostController::class, 'like'])->name('post.like');
    Route::delete('posts/like/{id}',  [PostController::class, 'unlike'])->name('post.like.delete');
    Route::post('posts/comments/{id}',  [PostController::class, 'comments'])->name('post.comments');
    Route::delete('posts/{id}',  [PostController::class, 'destroy'])->name('post.delete');

    // Route User
    Route::get('user', [UserController::class, 'index']);
    Route::get('user/follow-list',  [UserController::class, 'followinglist']);
    Route::get('user/follower-list',  [UserController::class, 'followerlist']);
    Route::get('user/ping', [UserController::class, 'ping']);
    Route::get('user/find',  [UserController::class, 'find']);
    Route::post('user/{id}',  [UserController::class, 'update']);
    Route::post('user/follow/{id}',  [UserController::class, 'follow']);
    Route::post('user/unfollow/{id}',  [UserController::class, 'unfollow']);
});
