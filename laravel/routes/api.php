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
    Route::resource('posts', PostController::class);
    Route::get('posts/ping', [PostController::class, 'ping']);

    Route::resource('posts/images', PostImages::class);
    Route::resource('posts/likes', PostLikes::class);
    Route::resource('posts/comments', PostComments::class);

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
