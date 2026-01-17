<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySalesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $date,
        public readonly int $ordersCount,
        public readonly int $itemsCount,
        public readonly int $totalCents,
        /** @var array<int, array{name:string, qty:int, revenue_cents:int}> */
        public readonly array $lines
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Daily sales report: {$this->date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.daily-sales-report',
            with: [
                'date' => $this->date,
                'ordersCount' => $this->ordersCount,
                'itemsCount' => $this->itemsCount,
                'totalCents' => $this->totalCents,
                'lines' => $this->lines,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
