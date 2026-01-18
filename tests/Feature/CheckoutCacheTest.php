<?php

use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('clears product cache after checkout', function () {
    // 1. Setup
    $user = User::factory()->create();
    $product = Product::query()->create([
        'name' => 'Cache Test Product',
        'price_cents' => 1000,
        'stock' => 10,
        'is_active' => true,
    ]);

    $productId = (int) $product->id;
    $cacheKey = "product:active:{$productId}";

    /** @var ProductRepositoryContract $productRepository */
    $productRepository = app(ProductRepositoryContract::class);

    // 2. Warm up cache
    // findActiveById should cache the product
    // Ensure the version key exists so increment works
    Cache::put('products:cache:v', 1, now()->addDay());

    $fetched = $productRepository->findActiveById($productId);

    // In ProductCacheRepository, the key is "v1:product:active:{$id}" initially
    $cacheKeyWithVersion = "v1:product:active:{$productId}";

    expect(Cache::has($cacheKeyWithVersion))->toBeTrue();

    // 3. Perform checkout
    /** @var CartService $cartService */
    $cartService = app(CartService::class);
    $cartService->addProduct($user->id, $productId, 2);

    /** @var CheckoutService $checkoutService */
    $checkoutService = app(CheckoutService::class);

    $checkoutService->checkout($user->id);

    // 4. Verify cache is cleared/invalidated
    // After checkout, version should be bumped to 2
    $version = (int) Cache::get('products:cache:v', 1);
    expect($version)->toBe(2);

    // The version bump mechanism in ProductCacheRepository invalidates all keys
    // that use the version in their key name.
    $newCacheKey = "v2:product:active:{$productId}";
    expect(Cache::has($newCacheKey))->toBeFalse();

    // 5. Verify data is updated when refetched (and re-cached with new version)
    $updatedProduct = $productRepository->findActiveById($productId);
    expect((int) $updatedProduct->stock)->toBe(8);
    expect(Cache::has($newCacheKey))->toBeTrue();
    expect(Cache::get($newCacheKey)->stock)->toBe(8);
});
