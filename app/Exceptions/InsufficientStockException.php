<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(int $productId)
    {
        parent::__construct("Not enough stock for product {$productId}");
    }
}
