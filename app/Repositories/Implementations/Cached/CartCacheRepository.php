<?php

namespace App\Repositories\Implementations\Cached;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CartCacheRepository implements CartRepositoryContract
{
    public function __construct(
        private readonly CartRepositoryContract $inner
    ) {}

    public function getOrCreateByUserId(int $userId): Cart
    {
        $cart = $this->inner->getOrCreateByUserId($userId);

        Cache::put($this->keyCartByUser($userId), $cart, now()->addMinutes(10));

        return $cart;
    }

    public function getByUserId(int $userId): ?Cart
    {
        return Cache::remember(
            $this->keyCartByUser($userId),
            now()->addMinutes(10),
            fn () => $this->inner->getByUserId($userId)
        );
    }

    public function getItemsWithProducts(int $cartId): Collection
    {
        return Cache::remember(
            $this->keyCartItems($cartId),
            now()->addMinutes(5),
            fn () => $this->inner->getItemsWithProducts($cartId)
        );
    }

    public function getItem(int $cartId, int $productId): ?CartItem
    {
        return $this->inner->getItem($cartId, $productId);
    }

    public function upsertItemQuantity(int $cartId, int $productId, int $quantity): CartItem
    {
        $item = $this->inner->upsertItemQuantity($cartId, $productId, $quantity);

        $this->flushCartComputed($cartId);

        return $item;
    }

    public function incrementItemQuantity(int $cartId, int $productId, int $delta): CartItem
    {
        $item = $this->inner->incrementItemQuantity($cartId, $productId, $delta);

        $this->flushCartComputed($cartId);

        return $item;
    }

    public function removeItem(int $cartId, int $productId): void
    {
        $this->inner->removeItem($cartId, $productId);

        $this->flushCartComputed($cartId);
    }

    public function clear(int $cartId): void
    {
        $this->inner->clear($cartId);

        $this->flushCartComputed($cartId);
    }

    public function calculateTotalCents(int $cartId): string
    {
        return Cache::remember(
            $this->keyCartTotal($cartId),
            now()->addMinutes(5),
            fn () => $this->inner->calculateTotalCents($cartId)
        );
    }

    private function flushCartComputed(int $cartId): void
    {
        Cache::forget($this->keyCartItems($cartId));
        Cache::forget($this->keyCartTotal($cartId));
    }

    private function keyCartByUser(int $userId): string
    {
        return "cart:user:{$userId}";
    }

    private function keyCartItems(int $cartId): string
    {
        return "cart:{$cartId}:items";
    }

    private function keyCartTotal(int $cartId): string
    {
        return "cart:{$cartId}:total";
    }
}
