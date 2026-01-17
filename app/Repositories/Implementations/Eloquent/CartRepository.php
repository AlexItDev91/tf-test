<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartRepository implements CartRepositoryContract
{
    public function getOrCreateByUserId(int $userId): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $userId]);
    }

    public function getByUserId(int $userId): ?Cart
    {
        return Cart::query()->where('user_id', $userId)->first();
    }

    public function getItemsWithProducts(int $cartId): Collection
    {
        return CartItem::query()
            ->where('cart_id', $cartId)
            ->with('product')
            ->orderBy('id')
            ->get();
    }

    public function getItem(int $cartId, int $productId): ?CartItem
    {
        return CartItem::query()
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();
    }

    public function upsertItemQuantity(int $cartId, int $productId, int $quantity): CartItem
    {
        $item = CartItem::query()->firstOrNew([
            'cart_id' => $cartId,
            'product_id' => $productId,
        ]);

        $item->quantity = $quantity;
        $item->save();

        return $item;
    }

    public function incrementItemQuantity(int $cartId, int $productId, int $delta): CartItem
    {
        return DB::transaction(function () use ($cartId, $productId, $delta) {
            $item = CartItem::query()
                ->where('cart_id', $cartId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                return CartItem::query()->create([
                    'cart_id' => $cartId,
                    'product_id' => $productId,
                    'quantity' => max(1, $delta),
                ]);
            }

            $item->quantity = max(1, $item->quantity + $delta);
            $item->save();

            return $item;
        });
    }

    public function removeItem(int $cartId, int $productId): void
    {
        CartItem::query()
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->delete();
    }

    public function clear(int $cartId): void
    {
        CartItem::query()->where('cart_id', $cartId)->delete();
    }

    public function calculateTotal(int $cartId): string
    {
        $total = CartItem::query()
            ->where('cart_id', $cartId)
            ->join('products', 'products.id', '=', 'cart_items.product_id')
            ->selectRaw('COALESCE(SUM(products.price * cart_items.quantity), 0) AS total')
            ->value('total');

        return number_format((float)($total ?? 0), 2, '.', '');
    }
}
