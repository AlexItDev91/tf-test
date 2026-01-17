<?php

namespace App\DTOs;

readonly class DailySalesReportDto
{
    /**
     * @param  array<int, array{name: string, qty: int, revenue_cents: int}>  $lines
     */
    public function __construct(
        private int $ordersCount,
        private int $itemsCount,
        private int $totalCents,
        private array $lines,
    ) {}

    public function getOrdersCount(): int
    {
        return $this->ordersCount;
    }

    public function getItemsCount(): int
    {
        return $this->itemsCount;
    }

    public function getTotalCents(): int
    {
        return $this->totalCents;
    }

    /**
     * @return array<int, array{name: string, qty: int, revenue_cents: int}>
     */
    public function getLines(): array
    {
        return $this->lines;
    }
}
