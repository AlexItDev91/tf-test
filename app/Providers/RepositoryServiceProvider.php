<?php

namespace App\Providers;

use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Implementations\Cached\CartCacheRepository;
use App\Repositories\Implementations\Cached\ProductCacheRepository;
use App\Repositories\Implementations\Eloquent\CartRepository;
use App\Repositories\Implementations\Eloquent\ProductRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CartRepositoryContract::class, function ($app) {
            return new CartCacheRepository(
                $app->make(CartRepository::class)
            );
        });
        $this->app->bind(ProductRepositoryContract::class, function ($app) {
            return new ProductCacheRepository(
                $app->make(ProductRepository::class)
            );
        });
    }
}
