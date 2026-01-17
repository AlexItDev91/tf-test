<?php

use App\Events\ProductStockChanged;
use App\Models\Product;
use App\Repositories\Implementations\Eloquent\ProductRepository;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->repository = new ProductRepository();
});

test('it can find a product or fail', function () {
    $product = Product::factory()->create();

    $found = $this->repository->findOrFail($product->id);

    expect($found->id)->toBe($product->id);
});

test('it throws exception if product not found in findOrFail', function () {
    $this->repository->findOrFail(99999);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('it can find an active product by id', function () {
    $activeProduct = Product::factory()->create(['is_active' => true]);
    $inactiveProduct = Product::factory()->create(['is_active' => false]);

    expect($this->repository->findActiveById($activeProduct->id))->not->toBeNull();
    expect($this->repository->findActiveById($inactiveProduct->id))->toBeNull();
});

test('it can list active products', function () {
    Product::factory()->count(3)->create(['is_active' => true]);
    Product::factory()->count(2)->create(['is_active' => false]);

    $activeProducts = $this->repository->listActive();

    expect($activeProducts)->toHaveCount(3);
});

test('it can create a product', function () {
    $data = [
        'name' => 'New Product',
        'price_cents' => 1000,
        'stock' => 10,
        'is_active' => true,
    ];

    $product = $this->repository->create($data);

    expect($product->name)->toBe('New Product');
    $this->assertDatabaseHas('products', ['name' => 'New Product']);
});

test('it can update a product', function () {
    $product = Product::factory()->create(['name' => 'Old Name']);

    $updatedProduct = $this->repository->update($product->id, ['name' => 'New Name']);

    expect($updatedProduct->name)->toBe('New Name');
    $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);
});

test('it can delete a product', function () {
    $product = Product::factory()->create();

    $this->repository->delete($product->id);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('it can decrement stock if available', function () {
    Event::fake();
    $product = Product::factory()->create(['stock' => 10]);

    $result = $this->repository->decrementStockIfAvailable($product->id, 4);

    expect($result)->toBeTrue();
    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6]);

    Event::assertDispatched(ProductStockChanged::class, function ($event) use ($product) {
        return $event->product->id === $product->id &&
               $event->previousStock === 10 &&
               $event->newStock === 6;
    });
});

test('it returns false if stock is insufficient', function () {
    Event::fake();
    $product = Product::factory()->create(['stock' => 3]);

    $result = $this->repository->decrementStockIfAvailable($product->id, 5);

    expect($result)->toBeFalse();
    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
    Event::assertNotDispatched(ProductStockChanged::class);
});
