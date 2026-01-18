<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStockChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $previousStock,
        public readonly int $newStock,
    ) {}
}
