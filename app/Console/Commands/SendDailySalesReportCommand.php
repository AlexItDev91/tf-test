<?php

namespace App\Console\Commands;

use App\Jobs\SendDailySalesReportJob;
use Illuminate\Console\Command;

class SendDailySalesReportCommand extends Command
{
    protected $signature = 'report:daily-sales {--date= : YYYY-MM-DD (default: today)}';

    protected $description = 'Dispatch job to send daily sales report';

    public function handle(): int
    {
        $date = (string) ($this->option('date') ?: now()->toDateString());

        SendDailySalesReportJob::dispatch($date);

        $this->info("Daily sales report job dispatched for {$date}");

        return self::SUCCESS;
    }
}
