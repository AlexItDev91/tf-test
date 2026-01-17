<?php

namespace App\Repositories\Contracts;

use App\Models\Sale;

interface SaleRepositoryContract
{
    public function createPending(int $userId): Sale;

    public function addItems(int $saleId, array $items): void;

    public function updateTotal(int $saleId, string $total): void;

    public function setStatus(int $saleId, string $status): void;

    public function getWithItems(int $saleId): ?Sale;
}
