<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Events\ProductStockChanged;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        $updated = Product::query()
            ->whereKey($productId)
            ->where('stock', '>=', $quantity)
            ->update([
                'stock' => DB::raw("stock - {$quantity}"),
            ]);

        if ($updated !== 1) {
            return false;
        }

        $product = Product::query()->find($productId);

        event(new ProductStockChanged(
            product: $product,
            previousStock: $product->stock + $quantity,
            newStock: $product->stock,
        ));

        return true;
    }
}
