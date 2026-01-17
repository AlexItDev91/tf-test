<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Repositories\Contracts\ProductRepository::class, function ($app) {
            return new \App\Repositories\Implementations\Cached\ProductRepository(
                $app->make(\App\Repositories\Implementations\Eloquent\ProductRepository::class)
            );
        });
    }
}
