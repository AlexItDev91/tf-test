<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Implementations\Cached\CartCacheRepository;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->inner = Mockery::mock(CartRepositoryContract::class);
    $this->repository = new CartCacheRepository($this->inner);
    Cache::clear();
});

test('it puts cart in cache on getOrCreateByUserId', function () {
    $cart = Cart::factory()->make(['id' => 1, 'user_id' => 1]);
    $this->inner->shouldReceive('getOrCreateByUserId')->once()->with(1)->andReturn($cart);

    $this->repository->getOrCreateByUserId(1);

    expect(Cache::has('cart:user:1'))->toBeTrue();
});

test('it caches getByUserId', function () {
    $cart = Cart::factory()->make(['id' => 1, 'user_id' => 1]);
    $this->inner->shouldReceive('getByUserId')->once()->with(1)->andReturn($cart);

    $this->repository->getByUserId(1);
    $this->repository->getByUserId(1);

    expect(Cache::has('cart:user:1'))->toBeTrue();
});

test('it caches getItemsWithProducts', function () {
    $items = collect([CartItem::factory()->make()]);
    $this->inner->shouldReceive('getItemsWithProducts')->once()->with(1)->andReturn($items);

    $this->repository->getItemsWithProducts(1);
    $this->repository->getItemsWithProducts(1);

    expect(Cache::has('cart:1:items'))->toBeTrue();
});

test('it flushes cache on upsertItemQuantity', function () {
    $item = CartItem::factory()->make(['cart_id' => 1]);
    $this->inner->shouldReceive('upsertItemQuantity')->once()->with(1, 1, 5)->andReturn($item);

    $this->repository->upsertItemQuantity(1, 1, 5);

    expect(Cache::has('cart:1:items'))->toBeFalse();
    expect(Cache::has('cart:1:total'))->toBeFalse();
});

test('it flushes cache on incrementItemQuantity', function () {
    $item = CartItem::factory()->make(['cart_id' => 1]);
    $this->inner->shouldReceive('incrementItemQuantity')->once()->with(1, 1, 1)->andReturn($item);

    $this->repository->incrementItemQuantity(1, 1, 1);

    expect(Cache::has('cart:1:items'))->toBeFalse();
    expect(Cache::has('cart:1:total'))->toBeFalse();
});

test('it flushes cache on removeItem', function () {
    $this->inner->shouldReceive('removeItem')->once()->with(1, 1);

    $this->repository->removeItem(1, 1);

    expect(Cache::has('cart:1:items'))->toBeFalse();
    expect(Cache::has('cart:1:total'))->toBeFalse();
});

test('it flushes cache on clear', function () {
    $this->inner->shouldReceive('clear')->once()->with(1);

    $this->repository->clear(1);

    expect(Cache::has('cart:1:items'))->toBeFalse();
    expect(Cache::has('cart:1:total'))->toBeFalse();
});

test('it caches calculateTotalCents', function () {
    $this->inner->shouldReceive('calculateTotalCents')->once()->with(1)->andReturn(100);

    $this->repository->calculateTotalCents(1);
    $this->repository->calculateTotalCents(1);

    expect(Cache::has('cart:1:total'))->toBeTrue();
});
