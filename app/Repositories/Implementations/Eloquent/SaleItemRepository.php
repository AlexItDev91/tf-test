<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Models\SaleItem;
use App\Repositories\Contracts\SaleItemRepositoryContract;
use Illuminate\Support\Collection;

class SaleItemRepository implements SaleItemRepositoryContract
{
    public function getBySaleId(int $saleId): Collection
    {
        return SaleItem::query()
            ->where('sale_id', $saleId)
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): SaleItem
    {
        return SaleItem::query()->create($data);
    }

    public function bulkCreate(int $saleId, array $items): void
    {
        $now = now();

        $rows = [];

        foreach ($items as $item) {
            $rows[] = [
                'sale_id' => $saleId,
                'product_id' => (int) $item['product_id'],
                'product_name' => (string) $item['product_name'],
                'unit_price_cents' => (int) $item['unit_price_cents'],
                'quantity' => (int) $item['quantity'],
                'line_total_cents' => (int) $item['line_total_cents'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        SaleItem::query()->insert($rows);
    }

    public function deleteBySaleId(int $saleId): void
    {
        SaleItem::query()
            ->where('sale_id', $saleId)
            ->delete();
    }
}
