<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemReviewController;
use App\Http\Controllers\MauricioChatController;
use App\Http\Controllers\MeItemController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RewardPreviewController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('home', [HomeController::class, 'index']);
Route::get('companies', [CompanyController::class, 'index']);
Route::get('companies/{company}', [CompanyController::class, 'show']);
Route::get('companies/{company}/items', [CompanyController::class, 'items']);
Route::get('items', [ItemController::class, 'index']);
Route::get('items/{item}', [ItemController::class, 'show']);
Route::get('items/{item}/reviews', [ItemReviewController::class, 'index']);
Route::get('items/{item}/tags', [ItemController::class, 'tags']);
Route::get('reviews/{review}', [ReviewController::class, 'show']);
Route::get('reviews/{review}/comments', [CommentController::class, 'index']);
Route::get('customers', [CustomerController::class, 'index']);
Route::get('customers/{customer}', [CustomerController::class, 'show']);
Route::get('customers/{customer}/reviews', [CustomerController::class, 'reviews']);
Route::get('customers/{customer}/followers', [CustomerController::class, 'followers']);
Route::get('customers/{customer}/following', [CustomerController::class, 'following']);
Route::get('tags', [TagController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::patch('reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('reviews/{review}/author', [ReviewController::class, 'removeAuthor']);
    Route::put('me/preferences', [PreferencesController::class, 'update']);
    Route::get('me/wallet', [WalletController::class, 'show']);
    Route::get('me/reviews', [ReviewController::class, 'mine']);
    Route::get('me/items', [MeItemController::class, 'index']);
    Route::put('me/items/{item}/{state}', [MeItemController::class, 'update']);
    Route::delete('me/items/{item}/{state}', [MeItemController::class, 'destroy']);
    Route::get('feed', [FeedController::class, 'index']);
    Route::post('reviews/{review}/comments', [CommentController::class, 'store']);
    Route::patch('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    Route::put('reviews/{review}/reaction', [ReactionController::class, 'reactToReview']);
    Route::delete('reviews/{review}/reaction', [ReactionController::class, 'removeReviewReaction']);
    Route::put('comments/{comment}/reaction', [ReactionController::class, 'reactToComment']);
    Route::delete('comments/{comment}/reaction', [ReactionController::class, 'removeCommentReaction']);
    Route::put('customers/{customer}/follow', [CustomerController::class, 'follow']);
    Route::delete('customers/{customer}/follow', [CustomerController::class, 'unfollow']);
    Route::post('reports', [ReportController::class, 'store']);
    Route::post('items/{item}/mauricio-chat', [MauricioChatController::class, 'reply']);
    Route::post('items/{item}/reward-preview', [RewardPreviewController::class, 'show']);
});
