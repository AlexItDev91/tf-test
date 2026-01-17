<?php

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryContract;
use App\Repositories\Implementations\Cached\SaleCacheRepository;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->inner = Mockery::mock(SaleRepositoryContract::class);
    $this->repository = new SaleCacheRepository($this->inner);
    Cache::clear();
});

test('it delegates createPending to inner', function () {
    $sale = Sale::factory()->make(['id' => 1]);
    $this->inner->shouldReceive('createPending')->once()->with(1)->andReturn($sale);

    $result = $this->repository->createPending(1);

    expect($result)->toBe($sale);
});

test('it flushes cache on updateTotalCents', function () {
    $this->inner->shouldReceive('updateTotalCents')->once()->with(1, 1000);

    $this->repository->updateTotalCents(1, 1000);

    expect(Cache::has('sale:id:1'))->toBeFalse();
});

test('it flushes cache on setStatus', function () {
    $this->inner->shouldReceive('setStatus')->once()->with(1, SaleStatus::PAID);

    $this->repository->setStatus(1, SaleStatus::PAID);

    expect(Cache::has('sale:id:1'))->toBeFalse();
});

test('it caches getWithItems', function () {
    $sale = Sale::factory()->make(['id' => 1]);
    $this->inner->shouldReceive('getWithItems')->once()->with(1)->andReturn($sale);

    $this->repository->getWithItems(1);
    $this->repository->getWithItems(1);

    expect(Cache::has('sale:with_items:1'))->toBeTrue();
});

test('it caches getById', function () {
    $sale = Sale::factory()->make(['id' => 1]);
    $this->inner->shouldReceive('getById')->once()->with(1)->andReturn($sale);

    $this->repository->getById(1);
    $this->repository->getById(1);

    expect(Cache::has('sale:id:1'))->toBeTrue();
});

test('it delegates getByUserId to inner', function () {
    $sales = new \Illuminate\Database\Eloquent\Collection([Sale::factory()->make()]);
    $this->inner->shouldReceive('getByUserId')->once()->with(1)->andReturn($sales);

    $result = $this->repository->getByUserId(1);

    expect($result)->toBe($sales);
});

test('it delegates dailyReport to inner', function () {
    $dto = new \App\DTOs\DailySalesReportDto(0, 0, 0, []);
    $this->inner->shouldReceive('dailyReport')->once()->with('2024-01-01')->andReturn($dto);

    $result = $this->repository->dailyReport('2024-01-01');

    expect($result)->toBe($dto);
});
