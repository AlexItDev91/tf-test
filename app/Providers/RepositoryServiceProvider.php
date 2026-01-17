<?php

namespace App\Providers;

use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Implementations\Cached\CartCacheRepository;
use App\Repositories\Implementations\Cached\ProductCacheRepository;
use App\Repositories\Implementations\Eloquent\CartRepository;
use App\Repositories\Implementations\Eloquent\ProductRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\SaleRepositoryContract;
use App\Repositories\Implementations\Eloquent\SaleRepository;
use App\Repositories\Implementations\Cached\SaleCacheRepository;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use App\Repositories\Implementations\Eloquent\UserActionLogRepository;
use App\Repositories\Implementations\Cached\UserActionLogCacheRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserActionLogRepositoryContract::class, function ($app) {
            return new UserActionLogCacheRepository(
                $app->make(UserActionLogRepository::class)
            );
        });
        $this->app->bind(SaleRepositoryContract::class, function ($app) {
            return new SaleCacheRepository(
                $app->make(SaleRepository::class)
            );
        });
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
