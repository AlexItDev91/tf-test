<?php

namespace App\Services;

use App\Mail\DailySalesReportMail;
use App\Repositories\Contracts\SaleRepositoryContract;
use Illuminate\Support\Facades\Mail;

class SalesReportService
{
    public function __construct(
        private readonly SaleRepositoryContract $saleRepository
    ) {}

    public function sendDailyReport(string $dateYmd): void
    {
        $data = $this->saleRepository->dailyReport($dateYmd);

        Mail::to((string) config('shop.admin_email', 'admin@example.test'))
            ->send(new DailySalesReportMail(
                date: $dateYmd,
                ordersCount: (int) $data['ordersCount'],
                itemsCount: (int) $data['itemsCount'],
                totalCents: (int) $data['totalCents'],
                lines: (array) $data['lines'],
            ));
    }
}
