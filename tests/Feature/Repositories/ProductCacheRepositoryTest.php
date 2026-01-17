<?php

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Implementations\Cached\ProductCacheRepository;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->inner = Mockery::mock(ProductRepositoryContract::class);
    $this->repository = new ProductCacheRepository($this->inner);
    Cache::flush();
});

test('findActiveById caches the result', function () {
    $product = Product::factory()->make(['id' => 1, 'is_active' => true]);

    $this->inner->shouldReceive('findActiveById')
        ->once()
        ->with(1)
        ->andReturn($product);

    // First call - should call inner
    $result1 = $this->repository->findActiveById(1);
    expect($result1->id)->toBe(1);

    // Second call - should NOT call inner (cached)
    $result2 = $this->repository->findActiveById(1);
    expect($result2->id)->toBe(1);
});

test('create flushes the cache', function () {
    $product = Product::factory()->make(['id' => 1]);

    // Set some cache
    Cache::put('product:active:1', $product);

    $this->inner->shouldReceive('create')
        ->once()
        ->andReturn($product);

    $this->repository->create(['name' => 'New']);

    expect(Cache::has('product:active:1'))->toBeFalse();
});

test('update flushes the cache', function () {
    $product = Product::factory()->make(['id' => 1]);
    Cache::put('product:active:1', $product);

    $this->inner->shouldReceive('update')
        ->once()
        ->with(1, ['name' => 'Updated'])
        ->andReturn($product);

    $this->repository->update(1, ['name' => 'Updated']);

    expect(Cache::has('product:active:1'))->toBeFalse();
});

test('delete flushes the cache', function () {
    Cache::put('product:active:1', 'some-data');

    $this->inner->shouldReceive('delete')
        ->once()
        ->with(1);

    $this->repository->delete(1);

    expect(Cache::has('product:active:1'))->toBeFalse();
});

test('decrementStockIfAvailable flushes the cache on success', function () {
    Cache::put('product:active:1', 'some-data');

    $this->inner->shouldReceive('decrementStockIfAvailable')
        ->once()
        ->with(1, 5)
        ->andReturn(true);

    $this->repository->decrementStockIfAvailable(1, 5);

    expect(Cache::has('product:active:1'))->toBeFalse();
});

test('decrementStockIfAvailable does NOT flush the cache on failure', function () {
    Cache::put('product:active:1', 'some-data');

    $this->inner->shouldReceive('decrementStockIfAvailable')
        ->once()
        ->with(1, 5)
        ->andReturn(false);

    $this->repository->decrementStockIfAvailable(1, 5);

    expect(Cache::has('product:active:1'))->toBeTrue();
});
