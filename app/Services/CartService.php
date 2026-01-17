<?php

namespace App\Services;

use App\Enums\UserAction;
use App\Models\Cart;
use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use Illuminate\Support\Collection;
use RuntimeException;

class CartService
{
    public function __construct(
        private readonly CartRepositoryContract $cartRepository,
        private readonly ProductRepositoryContract $productRepository,
        private readonly UserActionLogRepositoryContract $userActionLogRepository,
    ) {}

    public function getCart(int $userId): Cart
    {
        return $this->cartRepository->getOrCreateByUserId($userId);
    }

    public function getItems(int $userId): Collection
    {
        $cart = $this->getCart($userId);

        return $this->cartRepository->getItemsWithProducts($cart->id);
    }

    public function addProduct(int $userId, int $productId, int $quantity = 1): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product = $this->productRepository->findActiveById($productId);

        if (! $product) {
            throw new RuntimeException('Product not available');
        }

        $cart = $this->getCart($userId);

        $this->cartRepository->incrementItemQuantity($cart->id, $productId, $quantity);

        $this->userActionLogRepository->log($userId, UserAction::CART_ADD, [
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    public function updateQuantity(int $userId, int $productId, int $quantity): void
    {
        $cart = $this->getCart($userId);

        if ($quantity <= 0) {
            $this->cartRepository->removeItem($cart->id, $productId);
        } else {
            $this->cartRepository->upsertItemQuantity($cart->id, $productId, $quantity);
        }

        $this->userActionLogRepository->log($userId, UserAction::CART_UPDATE_QUANTITY, [
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    public function removeProduct(int $userId, int $productId): void
    {
        $cart = $this->getCart($userId);

        $this->cartRepository->removeItem($cart->id, $productId);

        $this->userActionLogRepository->log($userId, UserAction::CART_REMOVE, [
            'product_id' => $productId,
        ]);
    }

    public function clear(int $userId): void
    {
        $cart = $this->getCart($userId);

        $this->cartRepository->clear($cart->id);

        $this->userActionLogRepository->log($userId, UserAction::CART_CLEAR);
    }

    public function totalCents(int $userId): int
    {
        $cart = $this->getCart($userId);

        return $this->cartRepository->calculateTotalCents($cart->id);
    }
}
