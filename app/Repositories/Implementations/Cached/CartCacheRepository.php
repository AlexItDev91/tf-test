<?php

namespace App\Repositories\Implementations\Cached;

use App\Repositories\Contracts\CartRepositoryContract;
use Illuminate\Support\Facades\Cache;
class CartCacheRepository implements CartRepositoryContract
{
    public function __construct(
        private readonly CartRepositoryContract $inner
    ) {}

    // Cached decorator implementation here.
}