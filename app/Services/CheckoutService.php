<?php

namespace App\Services;

use App\Models\Product;
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
                throw new RuntimeException('Cart is empty');
            }

            $sale = $this->saleRepository->createPending($userId);

            $saleItems = [];
            $total = 0.0;

            foreach ($items as $item) {
                /** @var Product|null $product */
                $product = $item->product;

                if (! $product) {
                    throw new RuntimeException('Product not available');
                }

                $ok = $this->productRepository->decrementStockIfAvailable($product->id, $item->quantity);

                if (! $ok) {
                    throw new RuntimeException("Not enough stock for product $product->id");
                }

                $unitPrice = (float) $product->price;
                $lineTotal = $unitPrice * (int) $item->quantity;
                $total += $lineTotal;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => number_format($unitPrice, 2, '.', ''),
                    'quantity' => (int) $item->quantity,
                    'line_total' => number_format($lineTotal, 2, '.', ''),
                ];
            }

            $this->saleItemRepository->bulkCreate($sale->id, $saleItems);

            $this->saleRepository->updateTotal($sale->id, number_format($total, 2, '.', ''));
            $this->saleRepository->setStatus($sale->id, 'paid');

            $this->cartRepository->clear($cart->id);

            $this->userActionLogRepository->log($userId, 'checkout.success', [
                'sale_id' => $sale->id,
                'total' => number_format($total, 2, '.', ''),
            ]);

            return $this->saleRepository->getWithItems($sale->id) ?? $sale;
        });
    }
}
