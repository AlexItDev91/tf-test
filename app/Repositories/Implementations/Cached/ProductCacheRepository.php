<?php

namespace App\Repositories\Implementations\Cached;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductCacheRepository implements ProductRepositoryContract
{
    public function __construct(
        private readonly ProductRepositoryContract $inner
    ) {}

    public function findOrFail(int $id): Product
    {
        return $this->inner->findOrFail($id);
    }

    public function findActiveById(int $id): ?Product
    {
        return Cache::remember(
            $this->keyActiveProduct($id),
            now()->addMinutes(10),
            fn () => $this->inner->findActiveById($id)
        );
    }

    public function listActive(int $limit = 50, int $offset = 0): Collection
    {
        return Cache::remember(
            $this->keyActiveList($limit, $offset),
            now()->addMinutes(5),
            fn () => $this->inner->listActive($limit, $offset)
        );
    }

    public function create(array $data): Product
    {
        $product = $this->inner->create($data);

        $this->flushAfterWrite((int) $product->getKey());

        return $product;
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->inner->update($id, $data);

        $this->flushAfterWrite($id);

        return $product;
    }

    public function delete(int $id): void
    {
        $this->inner->delete($id);

        $this->flushAfterWrite($id);
    }

    public function decrementStockIfAvailable(int $productId, int $quantity): bool
    {
        $ok = $this->inner->decrementStockIfAvailable($productId, $quantity);

        if ($ok) {
            $this->flushAfterWrite($productId);
        }

        return $ok;
    }

    private function flushAfterWrite(int $productId): void
    {
        $this->bumpVersion();
        Cache::forget($this->keyActiveProduct($productId));
    }

    private function keyActiveProduct(int $id): string
    {
        $v = $this->version();
        return "v{$v}:product:active:{$id}";
    }

    private function keyActiveList(int $limit, int $offset): string
    {
        $v = $this->version();
        return "v{$v}:products:active:list:limit:{$limit}:offset:{$offset}";
    }

    private function version(): int
    {
        return (int) Cache::get('products:cache:v', 1);
    }

    private function bumpVersion(): void
    {
        if (! Cache::has('products:cache:v')) {
            Cache::put('products:cache:v', 1, now()->addDay());
            return;
        }
        Cache::increment('products:cache:v');
    }
}
