<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Support\Collection;

interface ProductRepositoryContract
{
    public function findOrFail(int $id): Product;

    public function findActiveById(int $id): ?Product;

    public function listActive(int $limit = 50, int $offset = 0): Collection;

    public function create(array $data): Product;

    public function update(int $id, array $data): Product;

    public function delete(int $id): void;

    public function decrementStockIfAvailable(int $productId, int $quantity): bool;
}
