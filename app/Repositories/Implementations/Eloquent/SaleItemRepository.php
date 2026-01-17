<?php

namespace App\Repositories\Implementations\Eloquent;

use App\DTOs\SaleItemDataDto;
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

    /**
     * @param  SaleItemDataDto[]  $items
     */
    public function bulkCreate(int $saleId, array $items): void
    {
        $now = now();

        $rows = [];

        foreach ($items as $item) {
            $rows[] = [
                'sale_id' => $saleId,
                'product_id' => $item->getProductId(),
                'product_name' => $item->getProductName(),
                'unit_price_cents' => $item->getUnitPriceCents(),
                'quantity' => $item->getQuantity(),
                'line_total_cents' => $item->getLineTotalCents(),
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
