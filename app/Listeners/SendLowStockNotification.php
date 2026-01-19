<?php

namespace App\Listeners;

use App\Events\ProductStockChanged;
use App\Jobs\SendLowStockNotificationJob;

class SendLowStockNotification
{
    public bool $afterCommit = true;

    public function handle(ProductStockChanged $event): void
    {
        SendLowStockNotificationJob::dispatch(
            productId: (int) $event->productId,
            productName: (string) $event->productName,
            previousStock: (int) $event->previousStock,
            newStock: (int) $event->newStock,
        );
    }
}
