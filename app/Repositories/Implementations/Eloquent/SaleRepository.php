<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryContract;

class SaleRepository implements SaleRepositoryContract
{
    public function createPending(int $userId): Sale
    {
        return Sale::query()->create([
            'user_id' => $userId,
            'status' => SaleStatus::PENDING,
            'total_cents' => 0,
        ]);
    }

    public function updateTotalCents(int $saleId, int $totalCents): void
    {
        Sale::query()
            ->whereKey($saleId)
            ->update(['total_cents' => $totalCents]);
    }

    public function setStatus(int $saleId, SaleStatus $status): void
    {
        Sale::query()
            ->whereKey($saleId)
            ->update(['status' => $status]);
    }

    public function getWithItems(int $saleId): ?Sale
    {
        return Sale::query()
            ->with('items')
            ->find($saleId);
    }
}
