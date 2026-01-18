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
    Cache::put('products:cache:v', 1);

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

test('create flushes the cache by bumping version', function () {
    $product = Product::factory()->make(['id' => 1]);
    Cache::put('products:cache:v', 1);
    Cache::put('v1:product:active:1', $product);

    $this->inner->shouldReceive('create')
        ->once()
        ->andReturn($product);

    $this->repository->create(['name' => 'New']);

    expect((int) Cache::get('products:cache:v'))->toBe(2);
});

test('update flushes the cache by bumping version', function () {
    $product = Product::factory()->make(['id' => 1]);
    Cache::put('products:cache:v', 1);
    Cache::put('v1:product:active:1', $product);

    $this->inner->shouldReceive('update')
        ->once()
        ->with(1, ['name' => 'Updated'])
        ->andReturn($product);

    $this->repository->update(1, ['name' => 'Updated']);

    expect((int) Cache::get('products:cache:v'))->toBe(2);
});

test('delete flushes the cache by bumping version', function () {
    Cache::put('products:cache:v', 1);
    Cache::put('v1:product:active:1', 'some-data');

    $this->inner->shouldReceive('delete')
        ->once()
        ->with(1);

    $this->repository->delete(1);

    expect((int) Cache::get('products:cache:v'))->toBe(2);
});

test('decrementStockIfAvailable flushes the cache on success', function () {
    Cache::put('products:cache:v', 1);
    Cache::put('v1:product:active:1', 'some-data');

    $this->inner->shouldReceive('decrementStockIfAvailable')
        ->once()
        ->with(1, 5)
        ->andReturn(true);

    $this->repository->decrementStockIfAvailable(1, 5);

    expect((int) Cache::get('products:cache:v'))->toBe(2);
});

test('decrementStockIfAvailable does NOT flush the cache on failure', function () {
    Cache::put('products:cache:v', 1);
    Cache::put('v1:product:active:1', 'some-data');

    $this->inner->shouldReceive('decrementStockIfAvailable')
        ->once()
        ->with(1, 5)
        ->andReturn(false);

    $this->repository->decrementStockIfAvailable(1, 5);

    expect((int) Cache::get('products:cache:v', 1))->toBe(1);
});
