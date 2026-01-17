<?php

use App\Enums\UserAction;
use App\Exceptions\CartEmptyException;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Contracts\SaleItemRepositoryContract;
use App\Repositories\Contracts\SaleRepositoryContract;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use App\Services\CheckoutService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('throws CartEmptyException when cart is empty', function () {
    $cartRepository = Mockery::mock(CartRepositoryContract::class);
    $productRepository = Mockery::mock(ProductRepositoryContract::class);
    $saleRepository = Mockery::mock(SaleRepositoryContract::class);
    $saleItemRepository = Mockery::mock(SaleItemRepositoryContract::class);
    $userActionLogRepository = Mockery::mock(UserActionLogRepositoryContract::class);

    $userId = 1;
    $cart = new Cart();
    $cart->id = 100;

    $cartRepository->shouldReceive('getOrCreateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($cart);

    $cartRepository->shouldReceive('getItemsWithProducts')
        ->once()
        ->with($cart->id)
        ->andReturn(new Collection());

    $service = new CheckoutService(
        $cartRepository,
        $productRepository,
        $saleRepository,
        $saleItemRepository,
        $userActionLogRepository
    );

    DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

    $service->checkout($userId);
})->throws(CartEmptyException::class, 'Cart is empty');

it('throws InsufficientStockException when stock is not enough', function () {
    $cartRepository = Mockery::mock(CartRepositoryContract::class);
    $productRepository = Mockery::mock(ProductRepositoryContract::class);
    $saleRepository = Mockery::mock(SaleRepositoryContract::class);
    $saleItemRepository = Mockery::mock(SaleItemRepositoryContract::class);
    $userActionLogRepository = Mockery::mock(UserActionLogRepositoryContract::class);

    $userId = 1;
    $cart = new Cart();
    $cart->id = 100;

    $product = new Product();
    $product->id = 200;
    $product->price_cents = 1000;

    $item = new CartItem();
    $item->product = $product;
    $item->quantity = 5;

    $cartRepository->shouldReceive('getOrCreateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($cart);

    $cartRepository->shouldReceive('getItemsWithProducts')
        ->once()
        ->with($cart->id)
        ->andReturn(new Collection([$item]));

    $saleRepository->shouldReceive('createPending')
        ->once()
        ->with($userId)
        ->andReturn(new \App\Models\Sale());

    $productRepository->shouldReceive('decrementStockIfAvailable')
        ->once()
        ->with($product->id, 5)
        ->andReturn(false);

    $service = new CheckoutService(
        $cartRepository,
        $productRepository,
        $saleRepository,
        $saleItemRepository,
        $userActionLogRepository
    );

    DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

    $service->checkout($userId);
})->throws(InsufficientStockException::class, 'Not enough stock for product 200');

it('logs checkout success', function () {
    $cartRepository = Mockery::mock(CartRepositoryContract::class);
    $productRepository = Mockery::mock(ProductRepositoryContract::class);
    $saleRepository = Mockery::mock(SaleRepositoryContract::class);
    $saleItemRepository = Mockery::mock(SaleItemRepositoryContract::class);
    $userActionLogRepository = Mockery::mock(UserActionLogRepositoryContract::class);

    $userId = 1;
    $cart = new Cart();
    $cart->id = 100;

    $product = new Product();
    $product->id = 200;
    $product->name = 'Test Product';
    $product->price_cents = 1000;

    $item = new CartItem();
    $item->product = $product;
    $item->quantity = 2;

    $cartRepository->shouldReceive('getOrCreateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($cart);

    $cartRepository->shouldReceive('getItemsWithProducts')
        ->once()
        ->with($cart->id)
        ->andReturn(new Collection([$item]));

    $sale = new \App\Models\Sale();
    $sale->id = 500;
    $saleRepository->shouldReceive('createPending')
        ->once()
        ->with($userId)
        ->andReturn($sale);

    $productRepository->shouldReceive('decrementStockIfAvailable')
        ->once()
        ->with($product->id, 2)
        ->andReturn(true);

    $saleItemRepository->shouldReceive('bulkCreate')
        ->once();

    $saleRepository->shouldReceive('updateTotalCents')
        ->once()
        ->with($sale->id, 2000);

    $saleRepository->shouldReceive('setStatus')
        ->once()
        ->with($sale->id, \App\Enums\SaleStatus::PAID);

    $cartRepository->shouldReceive('clear')
        ->once()
        ->with($cart->id);

    $userActionLogRepository->shouldReceive('log')
        ->once()
        ->with($userId, UserAction::CHECKOUT_SUCCESS, $sale, [
            'sale_id' => 500,
            'total_cents' => 2000,
        ]);

    $saleRepository->shouldReceive('getWithItems')
        ->once()
        ->with($sale->id)
        ->andReturn($sale);

    $service = new CheckoutService(
        $cartRepository,
        $productRepository,
        $saleRepository,
        $saleItemRepository,
        $userActionLogRepository
    );

    DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

    $service->checkout($userId);
});
