<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Models\CartItem;
use App\Repositories\Contracts\CartItemRepositoryContract;
use Illuminate\Support\Collection;

class CartItemRepository implements CartItemRepositoryContract
{
    public function getByCartId(int $cartId): Collection
    {
        return CartItem::query()
            ->where('cart_id', $cartId)
            ->orderBy('id')
            ->get();
    }

    public function get(int $cartId, int $productId): ?CartItem
    {
        return CartItem::query()
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();
    }

    public function create(int $cartId, int $productId, int $quantity): CartItem
    {
        return CartItem::query()->create([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    public function updateQuantity(int $id, int $quantity): CartItem
    {
        $item = CartItem::query()->findOrFail($id);
        $item->quantity = $quantity;
        $item->save();

        return $item;
    }

    public function delete(int $id): void
    {
        CartItem::query()->whereKey($id)->delete();
    }

    public function deleteByCartAndProduct(int $cartId, int $productId): void
    {
        CartItem::query()
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->delete();
    }

    public function clearCart(int $cartId): void
    {
        CartItem::query()->where('cart_id', $cartId)->delete();
    }
}
