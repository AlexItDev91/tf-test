<?php

namespace App\Repositories\Contracts;

use App\Models\SaleItem;
use Illuminate\Support\Collection;

interface SaleItemRepositoryContract
{
    public function getBySaleId(int $saleId): Collection;

    public function create(array $data): SaleItem;

    public function bulkCreate(int $saleId, array $items): void;

    public function deleteBySaleId(int $saleId): void;
}
