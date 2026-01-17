<?php

use App\DTOs\SaleItemDataDto;

test('it can be instantiated and values can be retrieved via getters', function () {
    $dto = new SaleItemDataDto(
        productId: 1,
        productName: 'Test Product',
        unitPriceCents: 100,
        quantity: 2,
        lineTotalCents: 200
    );

    expect($dto->getProductId())->toBe(1)
        ->and($dto->getProductName())->toBe('Test Product')
        ->and($dto->getUnitPriceCents())->toBe(100)
        ->and($dto->getQuantity())->toBe(2)
        ->and($dto->getLineTotalCents())->toBe(200);
});
