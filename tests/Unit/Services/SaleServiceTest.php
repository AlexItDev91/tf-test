<?php

namespace Tests\Unit\Services;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryContract;
use App\Services\SaleService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use RuntimeException;

beforeEach(function () {
    $this->saleRepository = Mockery::mock(SaleRepositoryContract::class);
    $this->saleService = new SaleService($this->saleRepository);
});

afterEach(function () {
    Mockery::close();
});

it('lists sales by user', function () {
    $userId = 1;
    $sales = new Collection([new Sale(['id' => 1])]);

    $this->saleRepository->shouldReceive('getByUserId')
        ->once()
        ->with($userId)
        ->andReturn($sales);

    $result = $this->saleService->listByUser($userId);

    expect($result)->toBe($sales);
});

it('gets sale for user with items', function () {
    $userId = 1;
    $saleId = 10;
    $sale = new Sale(['id' => $saleId, 'user_id' => $userId]);

    $this->saleRepository->shouldReceive('getWithItems')
        ->once()
        ->with($saleId)
        ->andReturn($sale);

    $result = $this->saleService->getForUserWithItems($userId, $saleId);

    expect($result)->toBe($sale);
});

it('throws exception when sale not found', function () {
    $this->saleRepository->shouldReceive('getWithItems')
        ->once()
        ->andReturn(null);

    $this->saleService->getForUserWithItems(1, 10);
})->throws(RuntimeException::class, 'Sale not found');

it('throws exception when sale belongs to another user', function () {
    $userId = 1;
    $saleId = 10;
    $sale = new Sale(['id' => $saleId, 'user_id' => 2]);

    $this->saleRepository->shouldReceive('getWithItems')
        ->once()
        ->with($saleId)
        ->andReturn($sale);

    $this->saleService->getForUserWithItems($userId, $saleId);
})->throws(RuntimeException::class, 'Forbidden');
