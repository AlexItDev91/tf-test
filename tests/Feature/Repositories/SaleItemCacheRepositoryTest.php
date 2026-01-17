<?php

use App\Models\SaleItem;
use App\Repositories\Contracts\SaleItemRepositoryContract;
use App\Repositories\Implementations\Cached\SaleItemCacheRepository;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->inner = Mockery::mock(SaleItemRepositoryContract::class);
    $this->repository = new SaleItemCacheRepository($this->inner);
    Cache::clear();
});

test('it caches getBySaleId', function () {
    $items = collect([SaleItem::factory()->make(['sale_id' => 1])]);
    $this->inner->shouldReceive('getBySaleId')->once()->with(1)->andReturn($items);

    $this->repository->getBySaleId(1);
    $this->repository->getBySaleId(1);

    expect(Cache::has('sale_items:sale:1'))->toBeTrue();
});

test('it flushes cache on create', function () {
    $item = SaleItem::factory()->make(['sale_id' => 1]);
    $this->inner->shouldReceive('create')->once()->andReturn($item);

    $this->repository->create(['sale_id' => 1, 'product_name' => 'Test']);

    expect(Cache::has('sale_items:sale:1'))->toBeFalse();
});

test('it flushes cache on bulkCreate', function () {
    $this->inner->shouldReceive('bulkCreate')->once()->with(1, []);

    $this->repository->bulkCreate(1, []);

    expect(Cache::has('sale_items:sale:1'))->toBeFalse();
});

test('it flushes cache on deleteBySaleId', function () {
    $this->inner->shouldReceive('deleteBySaleId')->once()->with(1);

    $this->repository->deleteBySaleId(1);

    expect(Cache::has('sale_items:sale:1'))->toBeFalse();
});
