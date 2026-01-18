<?php

use App\Events\ProductStockChanged;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('dispatches ProductStockChanged event during checkout', function () {
    Event::fake([ProductStockChanged::class]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create([
        'stock' => 10,
    ]);

    // Add to cart
    $this->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertRedirect();

    // Checkout
    $this->post(route('checkout.store'))
        ->assertSuccessful();

    // Assert event was dispatched
    Event::assertDispatched(ProductStockChanged::class, function ($event) use ($product) {
        return (int) $event->productId === (int) $product->id &&
               (int) $event->newStock === 8;
    });
});
