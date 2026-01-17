<?php

namespace App\Repositories\Contracts;

use App\Enums\SaleStatus;
use App\Models\Sale;

interface SaleRepositoryContract
{
    public function createPending(int $userId): Sale;

    public function updateTotalCents(int $saleId, int $totalCents): void;

    public function setStatus(int $saleId, SaleStatus $status): void;

    public function getWithItems(int $saleId): ?Sale;
}
