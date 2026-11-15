<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemReviewController;
use App\Http\Controllers\MauricioChatController;
use App\Http\Controllers\MeItemController;
use App\Http\Controllers\PanelAuthController;
use App\Http\Controllers\PanelCoinController;
use App\Http\Controllers\PanelCouponController;
use App\Http\Controllers\PanelItemController;
use App\Http\Controllers\PanelItemMediaController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RewardPreviewController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('panel/login', [PanelAuthController::class, 'login']);
    Route::post('panel/register', [PanelAuthController::class, 'register']);
});

Route::middleware(['auth:sanctum', 'role:company,master', 'throttle:api'])->prefix('panel')->group(function () {
    Route::get('me', [PanelAuthController::class, 'me']);
    Route::patch('me', [PanelAuthController::class, 'update']);
    Route::put('me/password', [PanelAuthController::class, 'changePassword']);
    Route::post('logout', [PanelAuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'role:company', 'throttle:api'])->prefix('panel')->group(function () {
    Route::get('items', [PanelItemController::class, 'index']);
    Route::post('items', [PanelItemController::class, 'store']);
    Route::get('items/{panelItem}', [PanelItemController::class, 'show']);
    Route::put('items/{panelItem}', [PanelItemController::class, 'update']);
    Route::patch('items/{panelItem}/status', [PanelItemController::class, 'status']);
    Route::get('items/{panelItem}/metrics', [PanelItemController::class, 'metrics']);
    Route::post('item-media', [PanelItemMediaController::class, 'store'])->middleware('throttle:uploads');
    Route::get('coin', [PanelCoinController::class, 'show']);
    Route::put('coin', [PanelCoinController::class, 'update']);
    Route::patch('coin/status', [PanelCoinController::class, 'status']);
    Route::get('coin/performance', [PanelCoinController::class, 'performance']);
    Route::get('coupons', [PanelCouponController::class, 'index']);
    Route::post('coupons', [PanelCouponController::class, 'store']);
    Route::post('coupons/{panelCoupon}/codes', [PanelCouponController::class, 'addCodes']);
    Route::delete('coupons/{panelCoupon}/codes/{code}', [PanelCouponController::class, 'removeCode'])->whereNumber('code');
    Route::delete('coupons/{panelCoupon}', [PanelCouponController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:customer', 'throttle:api'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('me', [AuthController::class, 'me']);
    Route::put('me/preferences', [PreferencesController::class, 'update']);
    Route::get('me/wallet', [WalletController::class, 'show']);
    Route::get('me/redemptions', [CouponController::class, 'mine']);
    Route::get('coupons/companies', [CouponController::class, 'companies']);
    Route::get('companies/{company}/coupons', [CouponController::class, 'forCompany']);
    Route::post('coupons/{coupon}/redeem', [CouponController::class, 'redeem'])->middleware('throttle:uploads');
    Route::get('me/reviews', [ReviewController::class, 'mine']);
    Route::get('me/items', [MeItemController::class, 'index']);
    Route::put('me/items/{item}/{state}', [MeItemController::class, 'update']);
    Route::delete('me/items/{item}/{state}', [MeItemController::class, 'destroy']);

    Route::get('home', [HomeController::class, 'index']);
    Route::get('companies', [CompanyController::class, 'index']);
    Route::get('companies/{company}', [CompanyController::class, 'show']);
    Route::get('companies/{company}/items', [CompanyController::class, 'items']);
    Route::get('items', [ItemController::class, 'index']);
    Route::get('items/{item}', [ItemController::class, 'show']);
    Route::get('items/{item}/reviews', [ItemReviewController::class, 'index']);
    Route::get('items/{item}/tags', [ItemController::class, 'tags']);
    Route::get('tags', [TagController::class, 'index']);

    Route::get('feed', [FeedController::class, 'index']);
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::get('reviews/{review}', [ReviewController::class, 'show']);
    Route::patch('reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('reviews/{review}/author', [ReviewController::class, 'removeAuthor']);
    Route::put('reviews/{review}/reaction', [ReactionController::class, 'reactToReview']);
    Route::delete('reviews/{review}/reaction', [ReactionController::class, 'removeReviewReaction']);
    Route::post('items/{item}/reward-preview', [RewardPreviewController::class, 'show']);
    Route::post('items/{item}/mauricio-chat', [MauricioChatController::class, 'reply']);

    Route::get('reviews/{review}/comments', [CommentController::class, 'index']);
    Route::post('reviews/{review}/comments', [CommentController::class, 'store']);
    Route::patch('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    Route::put('comments/{comment}/reaction', [ReactionController::class, 'reactToComment']);
    Route::delete('comments/{comment}/reaction', [ReactionController::class, 'removeCommentReaction']);

    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{customer}', [CustomerController::class, 'show']);
    Route::get('customers/{customer}/reviews', [CustomerController::class, 'reviews']);
    Route::get('customers/{customer}/followers', [CustomerController::class, 'followers']);
    Route::get('customers/{customer}/following', [CustomerController::class, 'following']);
    Route::put('customers/{customer}/follow', [CustomerController::class, 'follow']);
    Route::delete('customers/{customer}/follow', [CustomerController::class, 'unfollow']);

    Route::post('reports', [ReportController::class, 'store']);
});
