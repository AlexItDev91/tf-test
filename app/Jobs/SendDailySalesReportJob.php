<?php

namespace App\Jobs;

use App\Mail\DailySalesReportMail;
use App\Repositories\Contracts\SaleRepositoryContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDailySalesReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $dateYmd
    ) {}

    public function handle(SaleRepositoryContract $saleRepository): void
    {
        $data = $saleRepository->dailyReport($this->dateYmd);

        Mail::to((string) config('shop.admin_email', 'admin@example.test'))
            ->send(new DailySalesReportMail(
                date: $this->dateYmd,
                ordersCount: (int) $data['ordersCount'],
                itemsCount: (int) $data['itemsCount'],
                totalCents: (int) $data['totalCents'],
                lines: (array) $data['lines'],
            ));
    }
}
