<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $stockLeft,
    ) {}

    public function build(): self
    {
        return $this->subject('Low stock alert')
            ->markdown('emails.low-stock', [
                'productId' => $this->productId,
                'productName' => $this->productName,
                'stockLeft' => $this->stockLeft,
            ]);
    }
}
