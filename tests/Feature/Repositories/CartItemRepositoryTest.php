<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Repositories\Implementations\Eloquent\CartItemRepository;

beforeEach(function () {
    $this->repository = new CartItemRepository();
});

test('it can get items by cart id', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->count(2)->create(['cart_id' => $cart->id]);

    $items = $this->repository->getByCartId($cart->id);

    expect($items)->toHaveCount(2);
});

test('it can get a specific cart item', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

    $item = $this->repository->get($cart->id, $product->id);

    expect($item->product_id)->toBe($product->id);
});

test('it can create a cart item', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();

    $item = $this->repository->create($cart->id, $product->id, 5);

    expect($item->quantity)->toBe(5);
    $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5]);
});

test('it can update quantity', function () {
    $item = CartItem::factory()->create(['quantity' => 1]);

    $updated = $this->repository->updateQuantity($item->id, 10);

    expect($updated->quantity)->toBe(10);
    $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 10]);
});

test('it can delete a cart item', function () {
    $item = CartItem::factory()->create();

    $this->repository->delete($item->id);

    $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
});

test('it can delete by cart and product', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

    $this->repository->deleteByCartAndProduct($cart->id, $product->id);

    $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id, 'product_id' => $product->id]);
});

test('it can clear a cart', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->count(3)->create(['cart_id' => $cart->id]);

    $this->repository->clearCart($cart->id);

    expect(CartItem::where('cart_id', $cart->id)->count())->toBe(0);
});
