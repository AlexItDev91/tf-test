<?php

use App\Jobs\SendDailySalesReportJob;
use App\Mail\DailySalesReportMail;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SalesReportService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('dispatches the daily sales report job via command', function () {
    Queue::fake();

    $this->artisan('report:daily-sales')
        ->expectsOutput('Daily sales report job dispatched for '.now()->toDateString())
        ->assertSuccessful();

    Queue::assertPushed(SendDailySalesReportJob::class, function ($job) {
        return $job->dateYmd === now()->toDateString();
    });
});

it('dispatches the daily sales report job via command with custom date', function () {
    Queue::fake();

    $date = '2026-01-01';

    $this->artisan('report:daily-sales', ['--date' => $date])
        ->expectsOutput("Daily sales report job dispatched for {$date}")
        ->assertSuccessful();

    Queue::assertPushed(SendDailySalesReportJob::class, function ($job) {
        return $job->dateYmd === '2026-01-01';
    });
});

it('sends the daily sales report mail with correct data via job', function () {
    Mail::fake();

    $date = now()->toDateString();

    $sale = Sale::factory()->create(['created_at' => now(), 'total_cents' => 1000]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_name' => 'Test Product',
        'quantity' => 2,
        'line_total_cents' => 1000,
        'created_at' => now(),
    ]);

    // Sale for another day (should be ignored)
    Sale::factory()->create(['created_at' => now()->subDay()]);

    (new SendDailySalesReportJob($date))->handle(app(\App\Repositories\Contracts\SaleRepositoryContract::class));

    Mail::assertSent(DailySalesReportMail::class, function ($mail) use ($date) {
        return $mail->date === $date &&
               $mail->ordersCount === 1 &&
               $mail->itemsCount === 2 &&
               $mail->totalCents === 1000 &&
               count($mail->lines) === 1 &&
               $mail->lines[0]['name'] === 'Test Product';
    });
});

it('sends the daily sales report mail via service', function () {
    Mail::fake();

    $date = now()->toDateString();

    Sale::factory()->create(['created_at' => now(), 'total_cents' => 500]);

    app(SalesReportService::class)->sendDailyReport($date);

    Mail::assertSent(DailySalesReportMail::class);
});

it('is scheduled to run daily at 21:00', function () {
    $schedule = app(Schedule::class);

    $events = collect($schedule->events())->filter(function ($event) {
        return str_contains($event->command, 'report:daily-sales');
    });

    expect($events)->not->toBeEmpty();

    $event = $events->first();
    expect($event->expression)->toBe('0 21 * * *');
    expect($event->timezone)->toBe('Europe/Warsaw');
});
