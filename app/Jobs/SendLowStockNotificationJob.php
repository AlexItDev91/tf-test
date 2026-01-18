<?php

namespace App\Jobs;

use App\Mail\LowStockMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendLowStockNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $previousStock,
        public readonly int $newStock,
    ) {}

    public function handle(): void
    {
        $threshold = (int) config('shop.low_stock_threshold', 3);

        if ($this->newStock > $threshold) {
            return;
        }

        if ($this->previousStock <= $threshold) {
            return;
        }

        $cacheKey = "low_stock_notified:product:{$this->productId}:stock:{$this->newStock}";

        if (! Cache::add($cacheKey, true, now()->addHours(24))) {
            return;
        }

        Mail::to((string) config('shop.admin_email', 'admin@example.test'))
            ->send(new LowStockMail(
                productId: $this->productId,
                productName: $this->productName,
                stockLeft: $this->newStock,
            ));
    }
}
