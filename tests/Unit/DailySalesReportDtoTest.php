<?php

use App\DTOs\DailySalesReportDto;

test('it can be instantiated and values can be retrieved via getters', function () {
    $lines = [
        ['name' => 'Product A', 'qty' => 5, 'revenue_cents' => 5000],
        ['name' => 'Product B', 'qty' => 2, 'revenue_cents' => 2000],
    ];

    $dto = new DailySalesReportDto(
        ordersCount: 3,
        itemsCount: 7,
        totalCents: 7000,
        lines: $lines
    );

    expect($dto->getOrdersCount())->toBe(3)
        ->and($dto->getItemsCount())->toBe(7)
        ->and($dto->getTotalCents())->toBe(7000)
        ->and($dto->getLines())->toBe($lines);
});
