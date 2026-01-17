<?php

namespace App\Services;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryContract;
use Illuminate\Support\Collection;
use RuntimeException;

class SaleService
{
    public function __construct(
        private readonly SaleRepositoryContract $saleRepository
    ) {}

    public function listByUser(int $userId): Collection
    {
        return $this->saleRepository->getByUserId($userId);
    }

    public function getForUserWithItems(int $userId, int $saleId): Sale
    {
        $sale = $this->saleRepository->getWithItems($saleId);

        if (! $sale) {
            throw new RuntimeException('Sale not found');
        }

        if ((int) $sale->user_id !== $userId) {
            throw new RuntimeException('Forbidden');
        }

        return $sale;
    }
}
