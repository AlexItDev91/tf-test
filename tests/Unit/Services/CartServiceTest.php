<?php

namespace Tests\Unit\Services;

use App\Enums\UserAction;
use App\Models\Cart;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use App\Services\CartService;
use Mockery;
use RuntimeException;

beforeEach(function () {
    $this->cartRepository = Mockery::mock(CartRepositoryContract::class);
    $this->productRepository = Mockery::mock(ProductRepositoryContract::class);
    $this->userActionLogRepository = Mockery::mock(UserActionLogRepositoryContract::class);

    $this->cartService = new CartService(
        $this->cartRepository,
        $this->productRepository,
        $this->userActionLogRepository
    );
});

afterEach(function () {
    Mockery::close();
});

it('gets cart for user', function () {
    $userId = 1;
    $cart = new Cart(['id' => 10, 'user_id' => $userId]);

    $this->cartRepository->shouldReceive('getOrCreateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($cart);

    $result = $this->cartService->getCart($userId);

    expect($result)->toBe($cart);
});

it('adds product to cart', function () {
    $userId = 1;
    $productId = 5;
    $quantity = 2;
    $cart = new Cart(['id' => 10, 'user_id' => $userId]);
    $cart->id = 10;
    $product = new Product(['id' => $productId, 'is_active' => true]);
    $product->id = $productId;

    $this->productRepository->shouldReceive('findActiveById')
        ->once()
        ->with($productId)
        ->andReturn($product);

    $this->cartRepository->shouldReceive('getOrCreateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($cart);

    $this->cartRepository->shouldReceive('incrementItemQuantity')
        ->once()
        ->with(10, $productId, $quantity);

    $this->userActionLogRepository->shouldReceive('log')
        ->once()
        ->with($userId, UserAction::CART_ADD, $cart, [
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

    $this->cartService->addProduct($userId, $productId, $quantity);
});

it('throws exception when adding inactive product', function () {
    $userId = 1;
    $productId = 5;

    $this->productRepository->shouldReceive('findActiveById')
        ->once()
        ->with($productId)
        ->andReturn(null);

    $this->cartService->addProduct($userId, $productId, 1);
})->throws(RuntimeException::class, 'Product not available');

it('does nothing when adding zero or negative quantity', function () {
    $this->productRepository->shouldNotReceive('findActiveById');
    $this->cartService->addProduct(1, 5, 0);
    $this->cartService->addProduct(1, 5, -1);
});

it('updates quantity to positive value', function () {
    $userId = 1;
    $productId = 5;
    $quantity = 3;
    $cart = new Cart(['id' => 10, 'user_id' => $userId]);
    $cart->id = 10;

    $this->cartRepository->shouldReceive('getOrCreateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($cart);

    $this->cartRepository->shouldReceive('upsertItemQuantity')
        ->once()
        ->with(10, $productId, $quantity);

    $this->userActionLogRepository->shouldReceive('log')
        ->once()
        ->with($userId, UserAction::CART_UPDATE_QUANTITY, $cart, [
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

    $this->cartService->updateQuantity($userId, $productId, $quantity);
});

it('removes product when updating quantity to zero or less', function () {
    $userId = 1;
    $productId = 5;
    $cart = new Cart(['id' => 10, 'user_id' => $userId]);
    $cart->id = 10;

    $this->cartRepository->shouldReceive('getOrCreateByUserId')
        ->andReturn($cart);

    $this->cartRepository->shouldReceive('removeItem')
        ->once()
        ->with(10, $productId);

    $this->userActionLogRepository->shouldReceive('log')
        ->once()
        ->with($userId, UserAction::CART_UPDATE_QUANTITY, $cart, [
            'product_id' => $productId,
            'quantity' => 0,
        ]);

    $this->cartService->updateQuantity($userId, $productId, 0);
});

it('removes product from cart', function () {
    $userId = 1;
    $productId = 5;
    $cart = new Cart(['id' => 10, 'user_id' => $userId]);
    $cart->id = 10;

    $this->cartRepository->shouldReceive('getOrCreateByUserId')
        ->andReturn($cart);

    $this->cartRepository->shouldReceive('removeItem')
        ->once()
        ->with(10, $productId);

    $this->userActionLogRepository->shouldReceive('log')
        ->once()
        ->with($userId, UserAction::CART_REMOVE, $cart, [
            'product_id' => $productId,
        ]);

    $this->cartService->removeProduct($userId, $productId);
});

it('clears cart', function () {
    $userId = 1;
    $cart = new Cart(['id' => 10, 'user_id' => $userId]);
    $cart->id = 10;

    $this->cartRepository->shouldReceive('getOrCreateByUserId')
        ->andReturn($cart);

    $this->cartRepository->shouldReceive('clear')
        ->once()
        ->with(10);

    $this->userActionLogRepository->shouldReceive('log')
        ->once()
        ->with($userId, UserAction::CART_CLEAR, $cart);

    $this->cartService->clear($userId);
});
