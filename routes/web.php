<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::get('/products', [ProductController::class, 'index'])
        ->name('products.index');

    Route::get('/cart', [CartController::class, 'show'])
        ->name('cart.show');

    Route::post('/cart/items', [CartController::class, 'store'])
        ->name('cart.items.store');

    Route::patch('/cart/items/{product}', [CartController::class, 'update'])
        ->name('cart.items.update');

    Route::delete('/cart/items/{product}', [CartController::class, 'destroy'])
        ->name('cart.items.destroy');

    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->name('checkout.store');
});
