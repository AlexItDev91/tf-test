<?php

namespace App\Jobs;

use App\Mail\LowStockMail;
use App\Models\Product;
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
        public readonly int $productId
    ) {}

    public function handle(): void
    {
        $product = Product::query()->find($this->productId);

        if (! $product) {
            return;
        }

        $threshold = (int) config('shop.low_stock_threshold', 3);

        if ((int) $product->stock > $threshold) {
            return;
        }

        $cacheKey = "low_stock_notified:product:{$product->id}:stock:{$product->stock}";

        if (! Cache::add($cacheKey, true, now()->addHours(24))) {
            return;
        }

        Mail::to((string) config('shop.admin_email', 'admin@example.test'))
            ->send(new LowStockMail($product));
    }
}
