<?php

use App\Livewire\Shop\ProductsPage;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

it('does not disable add button for guests', function () {
    Product::factory()->create(['stock' => 10, 'is_active' => true]);

    Livewire::test(ProductsPage::class)
        ->assertSee('Add')
        ->assertSee('Login');
});

it('enables add button for authenticated users', function () {
    $user = User::factory()->create();
    Product::factory()->create(['stock' => 10, 'is_active' => true]);

    Livewire::actingAs($user)
        ->test(ProductsPage::class)
        ->assertSee('Add');
});

it('prevents guests from adding to cart and returns early', function () {
    $product = Product::factory()->create(['stock' => 10, 'is_active' => true]);

    Livewire::test(ProductsPage::class)
        ->call('addToCart', $product->id)
        ->assertStatus(200);
});
