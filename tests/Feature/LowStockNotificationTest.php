<?php

use App\Mail\LowStockMail;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('queues low stock mail after real checkout when stock crosses threshold', function () {
    config()->set('shop.low_stock_threshold', 3);
    config()->set('shop.admin_email', 'admin@example.test');

    Mail::fake();

    $user = User::factory()->create();

    $product = Product::query()->create([
        'name' => 'Test Product',
        'price_cents' => 1999,
        'stock' => 4,
        'is_active' => true,
    ]);

    /** @var CartService $cartService */
    $cartService = app(CartService::class);
    $cartService->addProduct($user->id, (int) $product->id, 2);

    /** @var CheckoutService $checkoutService */
    $checkoutService = app(CheckoutService::class);
    $sale = $checkoutService->checkout($user->id);

    expect((int) $sale->id)->toBeGreaterThan(0);

    $product->refresh();
    expect((int) $product->stock)->toBe(2);

    Mail::assertQueued(LowStockMail::class, function (LowStockMail $mail) use ($product) {
        return $mail->hasTo('admin@example.test')
            && (int) $mail->product->id === (int) $product->id
            && (string) $mail->product->name === 'Test Product'
            && (int) $mail->product->stock === 2;
    });
});

it('does not send notification when stock stays above threshold', function () {
    config()->set('shop.low_stock_threshold', 3);
    config()->set('shop.admin_email', 'admin@example.test');

    Mail::fake();

    $user = User::factory()->create();

    $product = Product::query()->create([
        'name' => 'High Stock Product',
        'price_cents' => 1000,
        'stock' => 10,
        'is_active' => true,
    ]);

    /** @var CartService $cartService */
    $cartService = app(CartService::class);
    $cartService->addProduct($user->id, (int) $product->id, 2);

    /** @var CheckoutService $checkoutService */
    $checkoutService = app(CheckoutService::class);
    $checkoutService->checkout($user->id);

    $product->refresh();
    expect((int) $product->stock)->toBe(8);

    Mail::assertNotQueued(LowStockMail::class);
});

it('does not send notification when stock was already below threshold', function () {
    config()->set('shop.low_stock_threshold', 3);
    config()->set('shop.admin_email', 'admin@example.test');

    Mail::fake();

    $user = User::factory()->create();

    $product = Product::query()->create([
        'name' => 'Already Low Stock Product',
        'price_cents' => 1000,
        'stock' => 2,
        'is_active' => true,
    ]);

    /** @var CartService $cartService */
    $cartService = app(CartService::class);
    $cartService->addProduct($user->id, (int) $product->id, 1);

    /** @var CheckoutService $checkoutService */
    $checkoutService = app(CheckoutService::class);
    $checkoutService->checkout($user->id);

    $product->refresh();
    expect((int) $product->stock)->toBe(1);

    Mail::assertNotQueued(LowStockMail::class);
});

it('does not send notification twice for the same stock level due to caching', function () {
    config()->set('shop.low_stock_threshold', 3);
    config()->set('shop.admin_email', 'admin@example.test');

    Mail::fake();

    $user = User::factory()->create();

    $product = Product::query()->create([
        'name' => 'Cache Test Product',
        'price_cents' => 1000,
        'stock' => 4,
        'is_active' => true,
    ]);

    /** @var CartService $cartService */
    $cartService = app(CartService::class);
    $cartService->addProduct($user->id, (int) $product->id, 2);

    /** @var CheckoutService $checkoutService */
    $checkoutService = app(CheckoutService::class);

    // First checkout: triggers notification
    $checkoutService->checkout($user->id);

    $product->refresh();
    expect((int) $product->stock)->toBe(2);

    Mail::assertQueued(LowStockMail::class, 1);

    // Manual reset of stock to 4 to trigger the threshold again
    $product->update(['stock' => 4]);

    // Second checkout: would normally trigger notification again (4 -> 2)
    // but cache should prevent it.
    $cartService->addProduct($user->id, (int) $product->id, 2);
    $checkoutService->checkout($user->id);

    $product->refresh();
    expect((int) $product->stock)->toBe(2);

    // Should still only have 1 queued mail
    Mail::assertQueued(LowStockMail::class, 1);
});
