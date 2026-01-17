<?php

namespace App\Repositories\Implementations\Cached;

use App\Models\SaleItem;
use App\Repositories\Contracts\SaleItemRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SaleItemCacheRepository implements SaleItemRepositoryContract
{
    public function __construct(
        private readonly SaleItemRepositoryContract $inner
    ) {}

    public function getBySaleId(int $saleId): Collection
    {
        return Cache::remember(
            $this->keyItems($saleId),
            now()->addMinutes(10),
            fn () => $this->inner->getBySaleId($saleId)
        );
    }

    public function create(array $data): SaleItem
    {
        $item = $this->inner->create($data);
        Cache::forget($this->keyItems((int) $data['sale_id']));

        return $item;
    }

    public function bulkCreate(int $saleId, array $items): void
    {
        $this->inner->bulkCreate($saleId, $items);
        Cache::forget($this->keyItems($saleId));
    }

    public function deleteBySaleId(int $saleId): void
    {
        $this->inner->deleteBySaleId($saleId);
        Cache::forget($this->keyItems($saleId));
    }

    private function keyItems(int $saleId): string
    {
        return "sale:{$saleId}:items";
    }
}
