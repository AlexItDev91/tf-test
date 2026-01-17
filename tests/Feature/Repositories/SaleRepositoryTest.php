<?php

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Repositories\Implementations\Eloquent\SaleRepository;

beforeEach(function () {
    $this->repository = new SaleRepository();
});

test('it can create a pending sale', function () {
    $user = User::factory()->create();

    $sale = $this->repository->createPending($user->id);

    expect($sale->user_id)->toBe($user->id);
    expect($sale->status)->toBe(SaleStatus::PENDING);
    expect($sale->total_cents)->toBe(0);
    $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'pending']);
});

test('it can update total cents', function () {
    $sale = Sale::factory()->create(['total_cents' => 0]);

    $this->repository->updateTotalCents($sale->id, 1000);

    $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total_cents' => 1000]);
});

test('it can set status', function () {
    $sale = Sale::factory()->create(['status' => SaleStatus::PENDING]);

    $this->repository->setStatus($sale->id, SaleStatus::PAID);

    $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'paid']);
});

test('it can get sale with items', function () {
    $sale = Sale::factory()->create();
    SaleItem::factory()->count(2)->create(['sale_id' => $sale->id]);

    $found = $this->repository->getWithItems($sale->id);

    expect($found->items)->toHaveCount(2);
});

test('it can get sales by user id', function () {
    $user = User::factory()->create();
    Sale::factory()->count(3)->create(['user_id' => $user->id]);

    $sales = $this->repository->getByUserId($user->id);

    expect($sales)->toHaveCount(3);
});

test('it can get sale by id', function () {
    $sale = Sale::factory()->create();

    $found = $this->repository->getById($sale->id);

    expect($found->id)->toBe($sale->id);
});

test('it can generate daily report', function () {
    $date = now()->toDateString();
    $sale = Sale::factory()->create(['status' => SaleStatus::PAID, 'total_cents' => 2000, 'created_at' => $date]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_name' => 'Product A',
        'quantity' => 2,
        'line_total_cents' => 2000
    ]);

    $report = $this->repository->dailyReport($date);

    expect($report->getOrdersCount())->toBe(1);
    expect($report->getTotalCents())->toBe(2000);
    expect($report->getItemsCount())->toBe(2);
    expect($report->getLines())->toHaveCount(1);
    expect($report->getLines()[0]['name'])->toBe('Product A');
});
