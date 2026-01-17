<?php

namespace App\Repositories\Contracts;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Collection;

interface CartRepositoryContract
{
    public function getOrCreateByUserId(int $userId): Cart;

    public function getByUserId(int $userId): ?Cart;

    public function getItemsWithProducts(int $cartId): Collection;

    public function getItem(int $cartId, int $productId): ?CartItem;

    public function upsertItemQuantity(int $cartId, int $productId, int $quantity): CartItem;

    public function incrementItemQuantity(int $cartId, int $productId, int $delta): CartItem;

    public function removeItem(int $cartId, int $productId): void;

    public function clear(int $cartId): void;

    public function calculateTotalCents(int $cartId): int;
}
