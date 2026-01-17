<?php

namespace App\Repositories\Implementations\Cached;

use App\Models\CartItem;
use App\Repositories\Contracts\CartItemRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CartItemCacheRepository implements CartItemRepositoryContract
{
    public function __construct(
        private readonly CartItemRepositoryContract $inner
    ) {}

    public function getByCartId(int $cartId): Collection
    {
        return Cache::remember(
            $this->keyItems($cartId),
            now()->addMinutes(5),
            fn () => $this->inner->getByCartId($cartId)
        );
    }

    public function get(int $cartId, int $productId): ?CartItem
    {
        return $this->inner->get($cartId, $productId);
    }

    public function create(int $cartId, int $productId, int $quantity): CartItem
    {
        $item = $this->inner->create($cartId, $productId, $quantity);
        Cache::forget($this->keyItems($cartId));

        return $item;
    }

    public function updateQuantity(int $id, int $quantity): CartItem
    {
        $item = $this->inner->updateQuantity($id, $quantity);
        Cache::forget($this->keyItems($item->cart_id));

        return $item;
    }

    public function delete(int $id): void
    {
        $item = CartItem::query()->find($id);
        $this->inner->delete($id);

        if ($item) {
            Cache::forget($this->keyItems($item->cart_id));
        }
    }

    public function deleteByCartAndProduct(int $cartId, int $productId): void
    {
        $this->inner->deleteByCartAndProduct($cartId, $productId);
        Cache::forget($this->keyItems($cartId));
    }

    public function clearCart(int $cartId): void
    {
        $this->inner->clearCart($cartId);
        Cache::forget($this->keyItems($cartId));
    }

    private function keyItems(int $cartId): string
    {
        return "cart:{$cartId}:items";
    }
}
