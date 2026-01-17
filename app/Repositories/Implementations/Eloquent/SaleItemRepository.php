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

    public function deleteBySaleId(int $saleId): void
    {
        SaleItem::query()
            ->where('sale_id', $saleId)
            ->delete();
    }
}
