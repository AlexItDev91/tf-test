<?php

namespace App\Repositories\Contracts;

use App\DTOs\DailySalesReportDto;
use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Collection;

interface SaleRepositoryContract
{
    public function createPending(int $userId): Sale;

    public function updateTotalCents(int $saleId, int $totalCents): void;

    public function setStatus(int $saleId, SaleStatus $status): void;

    public function getWithItems(int $saleId): ?Sale;

    public function getByUserId(int $userId): Collection;

    public function getById(int $saleId): ?Sale;

    public function dailyReport(string $dateYmd): DailySalesReportDto;
}
