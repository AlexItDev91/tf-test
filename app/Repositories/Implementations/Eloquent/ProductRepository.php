<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Events\ProductStockChanged;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Illuminate\Support\Collection;

class ProductRepository implements ProductRepositoryContract
{
    public function findOrFail(int $id): Product
    {
        return Product::query()->findOrFail($id);
    }

    public function findActiveById(int $id): ?Product
    {
        return Product::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->first();
    }

    public function listActive(int $limit = 50, int $offset = 0): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::query()->findOrFail($id);
        $product->fill($data);
        $product->save();

        return $product;
    }

    public function delete(int $id): void
    {
        Product::query()->whereKey($id)->delete();
    }

    public function decrementStockIfAvailable(int $productId, int $quantity): bool
    {
        $product = Product::query()->lockForUpdate()->find($productId);

        if (! $product || (int) $product->stock < $quantity) {
            return false;
        }

        $previousStock = (int) $product->stock;
        $newStock = $previousStock - $quantity;

        $product->stock = $newStock;
        $product->save();

        event(new ProductStockChanged(
            productId: (int) $product->id,
            productName: (string) $product->name,
            previousStock: $previousStock,
            newStock: $newStock,
        ));

        return true;
    }
}
