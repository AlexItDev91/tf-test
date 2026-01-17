<?php

namespace App\Repositories\Contracts;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Collection;
use JetBrains\PhpStorm\ArrayShape;

interface SaleRepositoryContract
{
    public function createPending(int $userId): Sale;

    public function updateTotalCents(int $saleId, int $totalCents): void;

    public function setStatus(int $saleId, SaleStatus $status): void;

    public function getWithItems(int $saleId): ?Sale;

    public function getByUserId(int $userId): Collection;

    public function getById(int $saleId): ?Sale;

    #[ArrayShape([
        'ordersCount' => 'int',
        'itemsCount' => 'int',
        'totalCents' => 'int',
        'lines' => 'array',
    ])]
    public function dailyReport(string $dateYmd): array;
}
