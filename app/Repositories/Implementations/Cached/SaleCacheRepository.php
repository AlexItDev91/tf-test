<?php

namespace App\Repositories\Implementations\Cached;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryContract;
use Illuminate\Support\Facades\Cache;

class SaleCacheRepository implements SaleRepositoryContract
{
    public function __construct(
        private readonly SaleRepositoryContract $inner
    ) {}

    public function createPending(int $userId): Sale
    {
        return $this->inner->createPending($userId);
    }

    public function updateTotalCents(int $saleId, int $totalCents): void
    {
        $this->inner->updateTotalCents($saleId, $totalCents);

        Cache::forget($this->cacheKey($saleId));
    }

    public function setStatus(int $saleId, string $status): void
    {
        $this->inner->setStatus($saleId, $status);

        Cache::forget($this->cacheKey($saleId));
    }

    public function getWithItems(int $saleId): ?Sale
    {
        return Cache::remember(
            $this->cacheKey($saleId),
            now()->addMinutes(10),
            fn () => $this->inner->getWithItems($saleId)
        );
    }

    private function cacheKey(int $saleId): string
    {
        return "sale:with_items:{$saleId}";
    }
}
