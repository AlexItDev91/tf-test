<?php

namespace App\Services;

use App\DTOs\SaleItemDataDto;
use App\Enums\SaleStatus;
use App\Enums\UserAction;
use App\Exceptions\CartEmptyException;
use App\Exceptions\InsufficientStockException;
use App\Models\CartItem;
use App\Models\Sale;
use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Contracts\SaleItemRepositoryContract;
use App\Repositories\Contracts\SaleRepositoryContract;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CheckoutService
{
    public function __construct(
        private readonly CartRepositoryContract $cartRepository,
        private readonly ProductRepositoryContract $productRepository,
        private readonly SaleRepositoryContract $saleRepository,
        private readonly SaleItemRepositoryContract $saleItemRepository,
        private readonly UserActionLogRepositoryContract $userActionLogRepository,
    ) {}

    /**
     * @throws Throwable
     */
    public function checkout(int $userId): Sale
    {
        return DB::transaction(function () use ($userId) {
            $cart = $this->cartRepository->getOrCreateByUserId($userId);
            $items = $this->cartRepository->getItemsWithProducts($cart->id);

            if ($items->isEmpty()) {
                throw new CartEmptyException('Cart is empty');
            }

            $sale = $this->saleRepository->createPending($userId);

            $saleItems = [];
            $totalCents = 0;

            /** @var CartItem|null $item */
            foreach ($items as $item) {

                $product = $item->product;

                if (! $product) {
                    throw new RuntimeException('Product not available');
                }

                $qty = (int) $item->quantity;

                $ok = $this->productRepository->decrementStockIfAvailable($product->id, $qty);

                if (! $ok) {
                    throw new InsufficientStockException((int) $product->id);
                }

                $unitCents = (int) $product->price_cents;
                $lineCents = $unitCents * $qty;

                $totalCents += $lineCents;

                $saleItems[] = new SaleItemDataDto(
                    productId: (int) $product->id,
                    productName: (string) $product->name,
                    unitPriceCents: $unitCents,
                    quantity: $qty,
                    lineTotalCents: $lineCents
                );
            }

            $this->saleItemRepository->bulkCreate($sale->id, $saleItems);

            $this->saleRepository->updateTotalCents($sale->id, $totalCents);
            $this->saleRepository->setStatus($sale->id, SaleStatus::PAID);

            $this->cartRepository->clear($cart->id);

            $this->userActionLogRepository->log($userId, UserAction::CHECKOUT_SUCCESS, $sale, [
                'sale_id' => (int) $sale->id,
                'total_cents' => $totalCents,
            ]);

            return $this->saleRepository->getWithItems($sale->id) ?? $sale;
        });
    }
}
