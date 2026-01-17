<?php

namespace App\Providers;

use App\Repositories\Contracts\CartItemRepositoryContract;
use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Contracts\SaleItemRepositoryContract;
use App\Repositories\Contracts\SaleRepositoryContract;
use App\Repositories\Contracts\TestRepositoryContract;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use App\Repositories\Contracts\UserRepositoryContract;
use App\Repositories\Implementations\Cached\CartCacheRepository;
use App\Repositories\Implementations\Cached\CartItemCacheRepository;
use App\Repositories\Implementations\Cached\ProductCacheRepository;
use App\Repositories\Implementations\Cached\SaleCacheRepository;
use App\Repositories\Implementations\Cached\SaleItemCacheRepository;
use App\Repositories\Implementations\Cached\TestCacheRepository;
use App\Repositories\Implementations\Cached\UserActionLogCacheRepository;
use App\Repositories\Implementations\Cached\UserCacheRepository;
use App\Repositories\Implementations\Eloquent\CartItemRepository;
use App\Repositories\Implementations\Eloquent\CartRepository;
use App\Repositories\Implementations\Eloquent\ProductRepository;
use App\Repositories\Implementations\Eloquent\SaleItemRepository;
use App\Repositories\Implementations\Eloquent\SaleRepository;
use App\Repositories\Implementations\Eloquent\TestRepository;
use App\Repositories\Implementations\Eloquent\UserActionLogRepository;
use App\Repositories\Implementations\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TestRepositoryContract::class, function ($app) {
            return new TestCacheRepository(
                $app->make(TestRepository::class)
            );
        });
        $this->app->bind(UserRepositoryContract::class, function ($app) {
            return new UserCacheRepository(
                $app->make(UserRepository::class)
            );
        });
        $this->app->bind(SaleItemRepositoryContract::class, function ($app) {
            return new SaleItemCacheRepository(
                $app->make(SaleItemRepository::class)
            );
        });
        $this->app->bind(CartItemRepositoryContract::class, function ($app) {
            return new CartItemCacheRepository(
                $app->make(CartItemRepository::class)
            );
        });
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
