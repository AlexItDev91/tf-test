<?php

namespace App\Repositories\Implementations\Cached;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryContract;

class SaleCacheRepository implements SaleRepositoryContract
{
    public function __construct(
        private readonly SaleRepositoryContract $inner
    ) {}

    public function createPending(int $userId): Sale
    {
        return $this->inner->createPending($userId);
    }

    public function addItems(int $saleId, array $items): void
    {
        $this->inner->addItems($saleId, $items);
    }

    public function updateTotal(int $saleId, string $total): void
    {
        $this->inner->updateTotal($saleId, $total);
    }

    public function setStatus(int $saleId, string $status): void
    {
        $this->inner->setStatus($saleId, $status);
    }

    public function getWithItems(int $saleId): ?Sale
    {
        return $this->inner->getWithItems($saleId);
    }
}
