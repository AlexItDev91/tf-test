<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Livewire\Shop\CartPage;
use App\Livewire\Shop\ProductsPage;
use App\Livewire\Shop\SaleShowPage;
use App\Livewire\Shop\SalesPage;
use Illuminate\Support\Facades\Route;

Route::get('/', ProductsPage::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::view('profile', 'profile')->name('profile');

    Route::prefix('shop')->name('shop.')->group(function () {
        Route::get('cart', CartPage::class)->name('cart');
        Route::get('sales', SalesPage::class)->name('sales');
        Route::get('sales/{sale}', SaleShowPage::class)->name('sales.show');
    });

    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'show'])->name('show');

        Route::prefix('items')->name('items.')->group(function () {
            Route::post('/', [CartController::class, 'store'])->name('store');
            Route::patch('{product}', [CartController::class, 'update'])->name('update');
            Route::delete('{product}', [CartController::class, 'destroy'])->name('destroy');
        });
    });

    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('{sale}', [SaleController::class, 'show'])->name('show');
    });
});

Route::get('/products', [ProductController::class, 'index'])
    ->name('products.index');

require __DIR__.'/auth.php';
