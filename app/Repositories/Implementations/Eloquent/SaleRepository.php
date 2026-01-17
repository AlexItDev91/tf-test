<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\Contracts\SaleRepositoryContract;

class SaleRepository implements SaleRepositoryContract
{
    public function createPending(int $userId): Sale
    {
        return Sale::query()->create([
            'user_id' => $userId,
            'status' => 'pending',
            'total_cents' => 0,
        ]);
    }

    public function addItems(int $saleId, array $items): void
    {
        foreach ($items as $item) {
            SaleItem::query()->create([
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unit_price_cents' => $item['unit_price_cents'],
                'quantity' => $item['quantity'],
                'line_total_cents' => $item['line_total_cents'],
            ]);
        }
    }

    public function setStatus(int $saleId, string $status): void
    {
        Sale::query()->whereKey($saleId)->update(['status' => $status]);
    }

    public function getWithItems(int $saleId): ?Sale
    {
        return Sale::query()
            ->with('items')
            ->whereKey($saleId)
            ->first();
    }

    public function updateTotalCents(int $saleId, int $totalCents): void
    {
        Sale::query()
            ->whereKey($saleId)
            ->update(['total_cents' => $totalCents]);
    }
}
