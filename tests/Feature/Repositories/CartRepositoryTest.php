<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Implementations\Eloquent\CartRepository;

beforeEach(function () {
    $this->repository = new CartRepository();
});

test('it can get or create a cart by user id', function () {
    $user = User::factory()->create();

    $cart = $this->repository->getOrCreateByUserId($user->id);

    expect($cart->user_id)->toBe($user->id);
    $this->assertDatabaseHas('carts', ['user_id' => $user->id]);

    $cart2 = $this->repository->getOrCreateByUserId($user->id);
    expect($cart2->id)->toBe($cart->id);
});

test('it can get a cart by user id', function () {
    $user = User::factory()->create();
    Cart::factory()->create(['user_id' => $user->id]);

    $found = $this->repository->getByUserId($user->id);

    expect($found->user_id)->toBe($user->id);
});

test('it can get items with products', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

    $items = $this->repository->getItemsWithProducts($cart->id);

    expect($items)->toHaveCount(1);
    expect($items[0]->product->id)->toBe($product->id);
});

test('it can get a specific item', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

    $item = $this->repository->getItem($cart->id, $product->id);

    expect($item->product_id)->toBe($product->id);
});

test('it can upsert item quantity', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();

    $item = $this->repository->upsertItemQuantity($cart->id, $product->id, 5);
    expect($item->quantity)->toBe(5);

    $item = $this->repository->upsertItemQuantity($cart->id, $product->id, 10);
    expect($item->quantity)->toBe(10);
    expect(CartItem::count())->toBe(1);
});

test('it can increment item quantity', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();

    // Create new
    $item = $this->repository->incrementItemQuantity($cart->id, $product->id, 2);
    expect($item->quantity)->toBe(2);

    // Increment existing
    $item = $this->repository->incrementItemQuantity($cart->id, $product->id, 3);
    expect($item->quantity)->toBe(5);

    // Decrement
    $item = $this->repository->incrementItemQuantity($cart->id, $product->id, -2);
    expect($item->quantity)->toBe(3);

    // Minimum 1
    $item = $this->repository->incrementItemQuantity($cart->id, $product->id, -10);
    expect($item->quantity)->toBe(1);
});

test('it can remove an item', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

    $this->repository->removeItem($cart->id, $product->id);

    $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id, 'product_id' => $product->id]);
});

test('it can clear a cart', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->count(3)->create(['cart_id' => $cart->id]);

    $this->repository->clear($cart->id);

    expect(CartItem::where('cart_id', $cart->id)->count())->toBe(0);
});

test('it can calculate total cents', function () {
    $cart = Cart::factory()->create();
    $p1 = Product::factory()->create(['price_cents' => 100]);
    $p2 = Product::factory()->create(['price_cents' => 200]);

    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $p1->id, 'quantity' => 2]);
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $p2->id, 'quantity' => 1]);

    $total = $this->repository->calculateTotalCents($cart->id);

    expect($total)->toBe(400);
});
