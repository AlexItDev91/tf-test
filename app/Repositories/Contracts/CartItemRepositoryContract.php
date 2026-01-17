<?php

namespace App\Repositories\Contracts;

use App\Models\CartItem;
use Illuminate\Support\Collection;

interface CartItemRepositoryContract
{
    public function getByCartId(int $cartId): Collection;

    public function get(int $cartId, int $productId): ?CartItem;

    public function create(int $cartId, int $productId, int $quantity): CartItem;

    public function updateQuantity(int $id, int $quantity): CartItem;

    public function delete(int $id): void;

    public function deleteByCartAndProduct(int $cartId, int $productId): void;

    public function clearCart(int $cartId): void;
}
