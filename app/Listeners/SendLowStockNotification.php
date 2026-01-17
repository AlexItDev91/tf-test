<?php

namespace App\Listeners;

use App\Events\ProductStockChanged;
use App\Mail\LowStockMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendLowStockNotification implements ShouldQueue
{
    public function handle(ProductStockChanged $event): void
    {
        $threshold = (int) config('shop.low_stock_threshold', 3);

        $newStock = (int) $event->newStock;

        if ($newStock > $threshold) {
            return;
        }


        if ((int) $event->previousStock <= $threshold) {
            return;
        }

        $product = $event->product;

        $cacheKey = "low_stock_notified:product:{$product->id}:stock:{$newStock}";

        if (! Cache::add($cacheKey, true, now()->addHours(24))) {
            return;
        }

        Mail::to((string) config('shop.admin_email', 'admin@example.test'))
            ->send(new LowStockMail($product));
    }
}
