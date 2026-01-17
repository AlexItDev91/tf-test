<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected array $listen = [
        \App\Events\ProductStockChanged::class => [
            \App\Listeners\SendLowStockNotification::class,
        ],
    ];
}
