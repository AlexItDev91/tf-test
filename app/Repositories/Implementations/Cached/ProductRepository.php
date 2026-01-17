<?php

namespace App\Repositories\Implementations\Cached;

use App\Repositories\Contracts\ProductRepository as ProductRepositoryContract;
use Illuminate\Support\Facades\Cache;
class ProductRepository implements ProductRepositoryContract
{
    public function __construct(
        private readonly ProductRepositoryContract $inner
    ) {}

    // Cached decorator implementation here.
}