<?php

use App\DTOs\SaleItemDataDto;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\Implementations\Eloquent\SaleItemRepository;

beforeEach(function () {
    $this->repository = new SaleItemRepository();
});

test('it can get sale items by sale id', function () {
    $sale = Sale::factory()->create();
    SaleItem::factory()->count(2)->create(['sale_id' => $sale->id]);

    $items = $this->repository->getBySaleId($sale->id);

    expect($items)->toHaveCount(2);
});

test('it can create a sale item', function () {
    $sale = Sale::factory()->create();
    $product = \App\Models\Product::factory()->create();
    $data = [
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price_cents' => $product->price_cents,
        'quantity' => 1,
        'line_total_cents' => $product->price_cents,
    ];

    $item = $this->repository->create($data);

    expect($item->product_name)->toBe($product->name);
    $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_id' => $product->id]);
});

test('it can bulk create sale items', function () {
    $sale = Sale::factory()->create();
    $product1 = \App\Models\Product::factory()->create(['name' => 'Product 1']);
    $product2 = \App\Models\Product::factory()->create(['name' => 'Product 2']);
    $dto1 = new SaleItemDataDto($product1->id, 'Product 1', 100, 2, 200);
    $dto2 = new SaleItemDataDto($product2->id, 'Product 2', 50, 4, 200);

    $this->repository->bulkCreate($sale->id, [$dto1, $dto2]);

    $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_id' => $product1->id]);
    $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_id' => $product2->id]);
    expect(SaleItem::where('sale_id', $sale->id)->count())->toBe(2);
});

test('it can delete sale items by sale id', function () {
    $sale = Sale::factory()->create();
    SaleItem::factory()->count(2)->create(['sale_id' => $sale->id]);

    $this->repository->deleteBySaleId($sale->id);

    $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
});
