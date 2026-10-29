<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MauricioChatController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\RewardPreviewController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('items', [ItemController::class, 'index']);
Route::get('items/{item}', [ItemController::class, 'show']);
Route::get('tags', [TagController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::put('me/preferences', [PreferencesController::class, 'update']);
    Route::get('me/wallet', [WalletController::class, 'show']);
    Route::get('me/reviews', [ReviewController::class, 'mine']);
    Route::post('items/{item}/mauricio-chat', [MauricioChatController::class, 'reply']);
    Route::post('items/{item}/reward-preview', [RewardPreviewController::class, 'show']);
});
