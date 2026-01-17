<?php

namespace App\DTOs;

readonly class SaleItemDataDto
{
    public function __construct(
        private int $productId,
        private string $productName,
        private int $unitPriceCents,
        private int $quantity,
        private int $lineTotalCents,
    ) {}

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getLineTotalCents(): int
    {
        return $this->lineTotalCents;
    }
}
