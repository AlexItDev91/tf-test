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
    $data = [
        'sale_id' => $sale->id,
        'product_id' => 1,
        'product_name' => 'Test Product',
        'unit_price_cents' => 100,
        'quantity' => 1,
        'line_total_cents' => 100,
    ];

    $item = $this->repository->create($data);

    expect($item->product_name)->toBe('Test Product');
    $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_name' => 'Test Product']);
});

test('it can bulk create sale items', function () {
    $sale = Sale::factory()->create();
    $dto1 = new SaleItemDataDto(1, 'Product 1', 100, 2, 200);
    $dto2 = new SaleItemDataDto(2, 'Product 2', 50, 4, 200);

    $this->repository->bulkCreate($sale->id, [$dto1, $dto2]);

    $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_name' => 'Product 1']);
    $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_name' => 'Product 2']);
    expect(SaleItem::where('sale_id', $sale->id)->count())->toBe(2);
});

test('it can delete sale items by sale id', function () {
    $sale = Sale::factory()->create();
    SaleItem::factory()->count(2)->create(['sale_id' => $sale->id]);

    $this->repository->deleteBySaleId($sale->id);

    $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
});
