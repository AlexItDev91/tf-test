<?php

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('checks out: creates sale + sale items, decrements stock, clears cart', function () {
    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'testpest@example.com',
        'password' => bcrypt('password'),
    ]);

    $product = Product::query()->create([
        'name' => 'Test Product',
        'price_cents' => 1299,
        'stock' => 10,
    ]);

    $cartService = app(CartService::class);

    $cartService->addProduct($user->id, $product->id, 3);

    $this->actingAs($user);

    $response = $this->postJson(route('checkout.store'));

    $response->assertOk()
        ->assertJsonStructure([
            'sale_id',
            'status',
            'total_cents',
        ])
        ->assertJson([
            'status' => SaleStatus::PAID->value,
            'total_cents' => 1299 * 3,
        ]);

    $saleId = (int) $response->json('sale_id');

    $sale = Sale::query()->findOrFail($saleId);

    expect((int) $sale->user_id)->toBe((int) $user->id)
        ->and($sale->status)->toBe(SaleStatus::PAID)
        ->and((int) $sale->total_cents)->toBe(1299 * 3);

    $items = SaleItem::query()->where('sale_id', $saleId)->get();

    expect($items)->toHaveCount(1)
        ->and((int) $items->first()->product_id)->toBe((int) $product->id)
        ->and((int) $items->first()->unit_price_cents)->toBe(1299)
        ->and((int) $items->first()->quantity)->toBe(3)
        ->and((int) $items->first()->line_total_cents)->toBe(1299 * 3);

    $product->refresh();
    expect((int) $product->stock)->toBe(7);

    $cart = $cartService->getCart($user->id);
    $cartItems = $cartService->getItems($user->id);

    expect((int) $cart->user_id)->toBe((int) $user->id)
        ->and($cartItems)->toHaveCount(0);
});

it('rejects checkout when cart is empty', function () {
    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'emptycart@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);

    $response = $this->postJson(route('checkout.store'));

    $response->assertStatus(400)
        ->assertJson(['message' => 'Cart is empty']);
});

it('rejects checkout when product is out of stock', function () {
    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'outofstock@example.com',
        'password' => bcrypt('password'),
    ]);

    $product = Product::query()->create([
        'name' => 'Limited Product',
        'price_cents' => 1000,
        'stock' => 2,
    ]);

    $cartService = app(CartService::class);
    $cartService->addProduct($user->id, $product->id, 5);

    $this->actingAs($user);

    $response = $this->postJson(route('checkout.store'));

    $response->assertStatus(400)
        ->assertJson(['message' => "Not enough stock for product {$product->id}"]);
});

it('checks out multiple products correctly', function () {
    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'multi@example.com',
        'password' => bcrypt('password'),
    ]);

    $p1 = Product::query()->create(['name' => 'P1', 'price_cents' => 100, 'stock' => 10]);
    $p2 = Product::query()->create(['name' => 'P2', 'price_cents' => 200, 'stock' => 10]);

    $cartService = app(CartService::class);
    $cartService->addProduct($user->id, $p1->id, 2);
    $cartService->addProduct($user->id, $p2->id, 3);

    $this->actingAs($user);

    $response = $this->postJson(route('checkout.store'));

    $response->assertOk()
        ->assertJson([
            'status' => SaleStatus::PAID->value,
            'total_cents' => (100 * 2) + (200 * 3),
        ]);

    $this->assertDatabaseHas('sales', [
        'user_id' => $user->id,
        'total_cents' => 800,
        'status' => SaleStatus::PAID->value,
    ]);

    $this->assertDatabaseHas('sale_items', ['product_id' => $p1->id, 'quantity' => 2]);
    $this->assertDatabaseHas('sale_items', ['product_id' => $p2->id, 'quantity' => 3]);

    $p1->refresh();
    $p2->refresh();
    expect($p1->stock)->toBe(8)
        ->and($p2->stock)->toBe(7);
});

it('requires authentication for checkout', function () {
    $response = $this->postJson(route('checkout.store'));
    $response->assertUnauthorized();
});
