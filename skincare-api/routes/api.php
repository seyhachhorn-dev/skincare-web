<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Throttled per IP to slow down credential-stuffing / registration
    // spam — 6 attempts/minute matches common practice (e.g. Fortify's
    // default login throttle).
    Route::middleware('throttle:6,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);
Route::get('categories', [CategoryController::class, 'index']);
Route::post('stripe/webhook', StripeWebhookController::class);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::patch('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{category}', [CategoryController::class, 'update']);
    Route::patch('categories/{category}', [CategoryController::class, 'update']);
    Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

    Route::get('users', [UserController::class, 'index']);
    Route::patch('users/{user}/role', [UserController::class, 'updateRole']);

    Route::get('admin/orders', [OrderController::class, 'adminIndex']);
    Route::patch('admin/orders/{order}/status', [OrderController::class, 'updateStatus']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('profile', [ProfileController::class, 'update']);

    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites', [FavoriteController::class, 'store']);
    Route::delete('favorites/{product}', [FavoriteController::class, 'destroy']);

    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/items', [CartController::class, 'store']);
    Route::patch('cart/items/{cartItem}', [CartController::class, 'update']);
    Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy']);

    Route::apiResource('addresses', AddressController::class)->except(['show']);

    Route::post('checkout', [CheckoutController::class, 'store']);
    Route::post('checkout/{order}/cancel', [CheckoutController::class, 'cancel']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);

    Route::post('orders/{order}/khqr', [PaymentController::class, 'generateKhqr']);
    Route::get('orders/{order}/khqr/status', [PaymentController::class, 'khqrStatus']);
    Route::post('orders/{order}/khqr/cancel', [PaymentController::class, 'cancelKhqr']);
});
